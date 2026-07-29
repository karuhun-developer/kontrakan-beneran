<?php

namespace Tests\Feature\Portal;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalStayTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Branch $branch;

    private Penyewa $penyewa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->branch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole(UserRole::Penyewa->value);

        $this->penyewa = Penyewa::factory()->forBranch($this->branch)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);
    }

    private function stay(?BookingStatus $status = null): Booking
    {
        return Booking::factory()
            ->forBranch($this->branch)
            ->status($status ?? BookingStatus::CheckedIn)
            ->create(['penyewa_id' => $this->penyewa->id]);
    }

    public function test_index_lists_only_the_penyewas_stays_and_reports_active_room()
    {
        $this->stay(BookingStatus::CheckedIn);
        $this->stay(BookingStatus::CheckedOut);

        $otherPenyewa = Penyewa::factory()->forBranch($this->branch)->create();
        Booking::factory()->forBranch($this->branch)->create(['penyewa_id' => $otherPenyewa->id]);

        $this->get(route('portal.stays.index'))
            ->assertInertia(fn ($page) => $page
                ->component('portal/stays/index')
                ->has('stays.data', 2)
                ->where('summary.total', 2)
                ->where('summary.active_room', fn ($value) => is_string($value) && $value !== '')
            );
    }

    public function test_can_view_own_stay_detail()
    {
        $stay = $this->stay();

        $this->get(route('portal.stays.show', $stay))
            ->assertOk();
    }

    public function test_cannot_view_another_penyewas_stay()
    {
        $otherPenyewa = Penyewa::factory()->forBranch($this->branch)->create();
        $stay = Booking::factory()->forBranch($this->branch)->create(['penyewa_id' => $otherPenyewa->id]);

        $this->get(route('portal.stays.show', $stay))
            ->assertNotFound();
    }

    public function test_staff_cannot_reach_the_portal_stays()
    {
        $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $staff->assignRole(UserRole::Staff->value);

        $this->actingAs($staff)
            ->get(route('portal.stays.index'))
            ->assertForbidden();
    }
}
