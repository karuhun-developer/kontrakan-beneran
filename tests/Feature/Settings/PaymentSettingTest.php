<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSettingTest extends TestCase
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

    private function actingAsRole(UserRole $role): User
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->assignRole($role->value);
        $user->branches()->sync([$this->branch->id]);

        $this->actingAs($user);
        $this->withSession(['current_branch_id' => $this->branch->id]);

        return $user;
    }

    public function test_owner_can_view_the_payment_settings_page()
    {
        $this->actingAsRole(UserRole::Owner);

        $this->get(route('payment-settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->component('payment-settings/index')
                ->where('branch.id', $this->branch->id)
            );
    }

    public function test_owner_can_update_the_branch_bank_details()
    {
        $this->actingAsRole(UserRole::Owner);

        $this->patch(route('payment-settings.update'), [
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Kost Demo Pusat',
        ])->assertRedirect();

        $this->assertDatabaseHas('branches', [
            'id' => $this->branch->id,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Kost Demo Pusat',
        ]);
    }

    public function test_staff_cannot_manage_payment_settings()
    {
        $this->actingAsRole(UserRole::Staff);

        $this->get(route('payment-settings.edit'))->assertForbidden();
    }
}
