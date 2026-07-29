<?php

namespace Tests\Feature\Master;

use App\Enums\CommonStatus;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Facility;
use App\Models\Penyewa;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterDataTest extends TestCase
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

    public function test_admin_can_create_a_room_type_scoped_to_current_branch()
    {
        $this->actingAsRole(UserRole::Admin);

        $this->post(route('room-types.store'), [
            'name' => 'Deluxe',
            'capacity' => 2,
            'monthly_price' => 1500000,
            'status' => CommonStatus::Active->value,
        ])->assertRedirect(route('room-types.index'));

        $this->assertDatabaseHas('room_types', [
            'branch_id' => $this->branch->id,
            'tenant_id' => $this->tenant->id,
            'name' => 'Deluxe',
        ]);
    }

    public function test_staff_cannot_create_a_room_type()
    {
        $this->actingAsRole(UserRole::Staff);

        $this->post(route('room-types.store'), [
            'name' => 'Deluxe',
            'capacity' => 2,
            'monthly_price' => 1500000,
            'status' => CommonStatus::Active->value,
        ])->assertForbidden();
    }

    public function test_admin_cannot_edit_a_room_type_from_another_branch()
    {
        $this->actingAsRole(UserRole::Admin);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        $foreignType = RoomType::factory()->forBranch($otherBranch)->create();

        $this->get(route('room-types.edit', $foreignType))->assertNotFound();
    }

    public function test_room_number_is_unique_within_a_branch()
    {
        $this->actingAsRole(UserRole::Admin);

        $type = RoomType::factory()->forBranch($this->branch)->create();
        Room::factory()->forBranch($this->branch)->create([
            'room_type_id' => $type->id,
            'number' => '101',
        ]);

        $this->post(route('rooms.store'), [
            'room_type_id' => $type->id,
            'number' => '101',
            'floor' => 1,
            'status' => CommonStatus::Active->value,
            'room_status' => RoomStatus::Available->value,
        ])->assertSessionHasErrors('number');
    }

    public function test_admin_can_create_a_room_with_facilities()
    {
        $this->actingAsRole(UserRole::Admin);

        $type = RoomType::factory()->forBranch($this->branch)->create();
        $facilities = Facility::factory()->forBranch($this->branch)->count(2)->create();

        $this->post(route('rooms.store'), [
            'room_type_id' => $type->id,
            'number' => '201',
            'floor' => 2,
            'status' => CommonStatus::Active->value,
            'room_status' => RoomStatus::Available->value,
            'facilities' => $facilities->pluck('id')->all(),
        ])->assertRedirect(route('rooms.index'));

        $room = Room::where('number', '201')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            $facilities->pluck('id')->all(),
            $room->facilities()->pluck('facilities.id')->all(),
        );
    }

    public function test_admin_can_create_a_penyewa_with_id_card_upload()
    {
        Storage::fake('public');
        $this->actingAsRole(UserRole::Admin);

        $this->post(route('penyewa.store'), [
            'name' => 'Budi',
            'status' => CommonStatus::Active->value,
            'id_card' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertRedirect(route('penyewa.index'));

        $penyewa = Penyewa::where('name', 'Budi')->firstOrFail();
        $this->assertEquals($this->branch->id, $penyewa->branch_id);
        $this->assertNotNull($penyewa->getFirstMedia(Penyewa::MEDIA_ID_CARD));
    }

    public function test_admin_can_open_a_deposit_when_creating_a_penyewa()
    {
        $this->actingAsRole(UserRole::Admin);

        $this->post(route('penyewa.store'), [
            'name' => 'Sinta',
            'status' => CommonStatus::Active->value,
            'deposit_amount' => 1_000_000,
        ])->assertRedirect(route('penyewa.index'));

        $penyewa = Penyewa::where('name', 'Sinta')->firstOrFail();

        $this->assertDatabaseHas('deposits', [
            'penyewa_id' => $penyewa->id,
            'branch_id' => $this->branch->id,
            'amount' => 1_000_000,
            'balance' => 1_000_000,
        ]);
    }

    public function test_creating_a_penyewa_without_a_deposit_amount_opens_no_deposit()
    {
        $this->actingAsRole(UserRole::Admin);

        $this->post(route('penyewa.store'), [
            'name' => 'Tanpa Deposit',
            'status' => CommonStatus::Active->value,
        ])->assertRedirect(route('penyewa.index'));

        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_index_only_shows_current_branch_rooms()
    {
        $this->actingAsRole(UserRole::Admin);

        $type = RoomType::factory()->forBranch($this->branch)->create();
        Room::factory()->forBranch($this->branch)->create(['room_type_id' => $type->id, 'number' => '301']);

        $otherBranch = Branch::factory()->create(['tenant_id' => $this->tenant->id]);
        Room::factory()->forBranch($otherBranch)->create(['number' => '301']);

        $this->get(route('rooms.index'))
            ->assertInertia(fn ($page) => $page
                ->component('master/rooms/index')
                ->has('rooms.data', 1)
            );
    }
}
