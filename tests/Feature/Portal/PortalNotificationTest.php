<?php

namespace Tests\Feature\Portal;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

/**
 * Minimal database notification for exercising the portal notification center.
 */
class SimpleNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['message' => 'Halo'];
    }
}

class PortalNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->assignRole(UserRole::Penyewa->value);

        Penyewa::factory()->forBranch($branch)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);
    }

    private function notify(): void
    {
        $this->user->notify(new SimpleNotification);
    }

    public function test_index_lists_the_penyewas_notifications()
    {
        $this->notify();

        $this->get(route('portal.notifications.index'))
            ->assertInertia(fn ($page) => $page
                ->component('portal/notifications/index')
                ->has('notifications.data', 1)
            );
    }

    public function test_can_mark_a_notification_as_read()
    {
        $this->notify();
        $notification = $this->user->notifications()->first();

        $this->post(route('portal.notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_can_mark_all_notifications_as_read()
    {
        $this->notify();
        $this->notify();

        $this->post(route('portal.notifications.read-all'))
            ->assertRedirect();

        $this->assertCount(0, $this->user->fresh()->unreadNotifications);
    }

    public function test_cannot_mark_another_users_notification()
    {
        $other = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $other->notify(new SimpleNotification);
        $notification = $other->notifications()->first();

        $this->post(route('portal.notifications.read', $notification->id))
            ->assertNotFound();
    }

    public function test_staff_cannot_reach_the_portal_notifications()
    {
        $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $staff->assignRole(UserRole::Staff->value);

        $this->actingAs($staff)
            ->get(route('portal.notifications.index'))
            ->assertForbidden();
    }
}
