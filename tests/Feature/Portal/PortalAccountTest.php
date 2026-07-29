<?php

namespace Tests\Feature\Portal;

use App\Actions\Portal\ProvisionPortalAccount;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalAccountTest extends TestCase
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
        $user->assignRole(UserRole::Admin->value);
        $user->branches()->sync([$this->branch->id]);

        $this->actingAs($user);
        $this->withSession(['current_branch_id' => $this->branch->id]);

        return $user;
    }

    private function penyewa(): Penyewa
    {
        return Penyewa::factory()->forBranch($this->branch)->create();
    }

    public function test_staff_can_provision_a_portal_login()
    {
        $this->actingAsStaff();
        $penyewa = $this->penyewa();

        $this->post(route('penyewa.account', $penyewa), [
            'email' => 'penyewa@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect(route('penyewa.index'));

        $penyewa->refresh();
        $this->assertNotNull($penyewa->user_id);

        $user = $penyewa->user;
        $this->assertTrue($user->hasRole(UserRole::Penyewa->value));
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->isStaff());
    }

    public function test_provisioned_penyewa_can_log_in_and_lands_on_the_portal()
    {
        $penyewa = $this->penyewa();
        app(ProvisionPortalAccount::class)->handle($penyewa, 'boarder@example.com', 'rahasia123');

        $response = $this->post(route('login.store'), [
            'email' => 'boarder@example.com',
            'password' => 'rahasia123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('portal.dashboard', absolute: false));
    }

    public function test_reprovisioning_resets_the_password_without_duplicating_the_user()
    {
        $penyewa = $this->penyewa();
        $action = app(ProvisionPortalAccount::class);
        $action->handle($penyewa, 'boarder@example.com', 'oldpassword1');
        $action->handle($penyewa->refresh(), 'boarder@example.com', 'newpassword1');

        $this->assertSame(1, User::where('email', 'boarder@example.com')->count());

        $this->post(route('login.store'), [
            'email' => 'boarder@example.com',
            'password' => 'newpassword1',
        ]);

        $this->assertAuthenticated();
    }

    public function test_email_must_be_unique_across_users()
    {
        $this->actingAsStaff();
        User::factory()->create(['email' => 'taken@example.com']);
        $penyewa = $this->penyewa();

        $this->post(route('penyewa.account', $penyewa), [
            'email' => 'taken@example.com',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');
    }

    public function test_blank_password_provisions_with_the_system_default()
    {
        $penyewa = $this->penyewa();
        $user = app(ProvisionPortalAccount::class)->handle($penyewa, 'boarder@example.com');

        $this->assertTrue(Hash::check(config('portal.default_password'), $user->password));

        $this->post(route('login.store'), [
            'email' => 'boarder@example.com',
            'password' => config('portal.default_password'),
        ]);

        $this->assertAuthenticated();
    }

    public function test_portal_flags_change_password_while_default_is_in_use()
    {
        $penyewa = $this->penyewa();
        $user = app(ProvisionPortalAccount::class)->handle($penyewa, 'boarder@example.com');

        $this->actingAs($user)
            ->get(route('portal.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('portal.must_change_password', true));
    }

    public function test_portal_does_not_flag_change_when_a_custom_password_is_set()
    {
        $penyewa = $this->penyewa();
        $user = app(ProvisionPortalAccount::class)->handle($penyewa, 'boarder@example.com', 'custompass1');

        $this->actingAs($user)
            ->get(route('portal.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('portal.must_change_password', false));
    }

    public function test_creating_a_penyewa_with_email_provisions_a_default_login()
    {
        $this->actingAsStaff();

        $this->post(route('penyewa.store'), [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'status' => 'active',
        ])->assertRedirect(route('penyewa.index'));

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(UserRole::Penyewa->value));
        $this->assertTrue(Hash::check(config('portal.default_password'), $user->password));
    }

    public function test_creating_a_penyewa_with_email_and_password_uses_that_password()
    {
        $this->actingAsStaff();

        $this->post(route('penyewa.store'), [
            'name' => 'Budi',
            'email' => 'budi2@example.com',
            'password' => 'custompass1',
            'status' => 'active',
        ])->assertRedirect(route('penyewa.index'));

        $this->post(route('login.store'), [
            'email' => 'budi2@example.com',
            'password' => 'custompass1',
        ]);

        $this->assertAuthenticated();
    }

    public function test_creating_a_penyewa_without_email_skips_provisioning()
    {
        $this->actingAsStaff();

        $this->post(route('penyewa.store'), [
            'name' => 'NoEmail',
            'status' => 'active',
        ])->assertRedirect(route('penyewa.index'));

        $this->assertSame(0, User::where('name', 'NoEmail')->count());
    }

    public function test_quick_create_with_email_provisions_a_default_login()
    {
        $this->actingAsStaff();

        $this->postJson(route('penyewa.quick-store'), [
            'name' => 'Quick Guest',
            'email' => 'quick@example.com',
        ])->assertOk();

        $user = User::where('email', 'quick@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(UserRole::Penyewa->value));
        $this->assertTrue(Hash::check(config('portal.default_password'), $user->password));
    }
}
