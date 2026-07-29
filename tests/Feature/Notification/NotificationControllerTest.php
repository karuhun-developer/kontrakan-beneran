<?php

namespace Tests\Feature\Notification;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InvoiceDueReminder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function actingAsStaff(): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole(UserRole::Staff->value);
        $user->branches()->sync([$this->branch->id]);

        $this->actingAs($user);
        $this->withSession(['current_branch_id' => $this->branch->id]);

        return $user;
    }

    private function notify(User $user): void
    {
        $invoice = Invoice::factory()->forBranch($this->branch)->create();
        $user->notify(new InvoiceDueReminder($invoice, 3));
    }

    public function test_index_lists_the_users_notifications()
    {
        $user = $this->actingAsStaff();
        $this->notify($user);

        $this->get(route('notifications.index'))
            ->assertInertia(fn ($page) => $page
                ->component('notifications/index')
                ->has('notifications.data', 1)
            );
    }

    public function test_index_only_shows_own_notifications()
    {
        $user = $this->actingAsStaff();
        $this->notify($user);

        $other = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->notify($other);

        $this->get(route('notifications.index'))
            ->assertInertia(fn ($page) => $page->has('notifications.data', 1));
    }

    public function test_shared_prop_exposes_unread_count_and_recent()
    {
        $user = $this->actingAsStaff();
        $this->notify($user);

        $this->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('notifications.unread_count', 1)
                ->has('notifications.recent', 1)
            );
    }

    public function test_mark_read_marks_a_single_notification()
    {
        $user = $this->actingAsStaff();
        $this->notify($user);
        $notification = $user->notifications()->first();

        $this->post(route('notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_mark_all_read_clears_the_unread_count()
    {
        $user = $this->actingAsStaff();
        $this->notify($user);
        $this->notify($user);

        $this->post(route('notifications.read-all'))->assertRedirect();

        $this->assertCount(0, $user->fresh()->unreadNotifications);
    }

    public function test_cannot_mark_another_users_notification_read()
    {
        $this->actingAsStaff();

        $other = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->notify($other);
        $notification = $other->notifications()->first();

        $this->post(route('notifications.read', $notification->id))
            ->assertNotFound();
    }
}
