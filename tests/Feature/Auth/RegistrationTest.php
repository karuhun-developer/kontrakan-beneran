<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_provisions_owner_tenant_with_default_data()
    {
        $this->post(route('register.store'), [
            'name' => 'Budi Owner',
            'email' => 'budi@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'budi@example.com')->firstOrFail();

        $this->assertNotNull($user->tenant_id);
        $this->assertTrue($user->hasRole(UserRole::Owner->value));

        $tenant = Tenant::findOrFail($user->tenant_id);
        $branch = Branch::where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertSame(1, Branch::where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, RoomType::where('branch_id', $branch->id)->count());
        $this->assertSame(1, Room::where('branch_id', $branch->id)->count());
    }
}
