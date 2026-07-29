<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\CommonStatus;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Facility;
use App\Models\Penyewa;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed a demo tenant with two branches and one user per staff role plus a
     * penyewa wired to the portal. All accounts use the password "password".
     */
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@kostpro.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $superAdmin->syncRoles(UserRole::SuperAdmin->value);

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'kost-demo'],
            [
                'name' => 'Kost Demo',
                'description' => 'Bisnis kost demo untuk pengembangan.',
                'status' => CommonStatus::Active,
            ],
        );

        $branchPusat = Branch::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'pusat'],
            [
                'name' => 'Cabang Pusat',
                'address' => 'Jl. Merdeka No. 1, Jakarta',
                'phone' => '021-1111111',
                'bank_name' => 'BCA',
                'bank_account_number' => '1234567890',
                'bank_account_holder' => 'Kost Demo Pusat',
                'status' => CommonStatus::Active,
            ],
        );

        $branchSelatan = Branch::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'selatan'],
            [
                'name' => 'Cabang Selatan',
                'address' => 'Jl. Kebayoran No. 2, Jakarta',
                'phone' => '021-2222222',
                'status' => CommonStatus::Active,
            ],
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner@kostpro.test'],
            [
                'name' => 'Owner Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ],
        );
        $owner->syncRoles(UserRole::Owner->value);

        $admin = User::firstOrCreate(
            ['email' => 'admin@kostpro.test'],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ],
        );
        $admin->syncRoles(UserRole::Admin->value);
        $admin->branches()->sync([$branchPusat->id, $branchSelatan->id]);

        $staff = User::firstOrCreate(
            ['email' => 'staff@kostpro.test'],
            [
                'name' => 'Staff Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ],
        );
        $staff->syncRoles(UserRole::Staff->value);
        $staff->branches()->sync([$branchPusat->id]);

        $penyewaUser = User::firstOrCreate(
            ['email' => 'penyewa@kostpro.test'],
            [
                'name' => 'Penyewa Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ],
        );
        $penyewaUser->syncRoles(UserRole::Penyewa->value);

        Penyewa::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'penyewa@kostpro.test'],
            [
                'branch_id' => $branchPusat->id,
                'user_id' => $penyewaUser->id,
                'name' => 'Penyewa Demo',
                'phone' => '0812-3456-7890',
                'id_card_number' => '3171000000000001',
                'gender' => 'male',
                'address' => 'Jl. Kos No. 3, Jakarta',
                'status' => CommonStatus::Active,
            ],
        );

        $this->seedMasterData($branchPusat);
        $this->seedMasterData($branchSelatan);

        $this->seedBookings($branchPusat);
    }

    /**
     * Seed a handful of bookings around the current month so the front-desk
     * timeline has data to display.
     */
    private function seedBookings(Branch $branch): void
    {
        $monthStart = now()->startOfMonth();

        $rooms = Room::forBranch($branch->id)->orderBy('number')->take(5)->get();

        if ($rooms->count() < 5) {
            return;
        }

        $plan = [
            ['name' => 'Rangga Wijaya', 'room' => 0, 'start' => 0, 'length' => 60, 'status' => BookingStatus::Confirmed],
            ['name' => 'Sinta Dewi', 'room' => 1, 'start' => 4, 'length' => 20, 'status' => BookingStatus::CheckedIn],
            ['name' => 'Bagas Pratama', 'room' => 3, 'start' => 10, 'length' => 40, 'status' => BookingStatus::Pending],
            ['name' => 'Nadia Putri', 'room' => 4, 'start' => -5, 'length' => 17, 'status' => BookingStatus::CheckedIn],
        ];

        foreach ($plan as $index => $item) {
            $room = $rooms[$item['room']];

            $penyewa = Penyewa::firstOrCreate(
                ['tenant_id' => $branch->tenant_id, 'name' => $item['name']],
                [
                    'branch_id' => $branch->id,
                    'phone' => '0812-0000-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'id_card_number' => '31710000000100'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'gender' => $index % 2 === 0 ? 'male' : 'female',
                    'status' => CommonStatus::Active,
                ],
            );

            $start = $monthStart->copy()->addDays($item['start']);
            $end = $start->copy()->addDays($item['length']);

            $alreadySeeded = Booking::where('branch_id', $branch->id)
                ->where('room_id', $room->id)
                ->whereDate('start_date', $start)
                ->exists();

            if ($alreadySeeded) {
                continue;
            }

            Booking::create([
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'room_id' => $room->id,
                'penyewa_id' => $penyewa->id,
                'start_date' => $start,
                'end_date' => $end,
                'monthly_price' => $room->effective_monthly_price,
                'status' => $item['status'],
            ]);
        }
    }

    /**
     * Seed room types, facilities, and rooms for a branch.
     */
    private function seedMasterData(Branch $branch): void
    {
        $facilities = collect(['AC', 'WiFi', 'Kamar Mandi Dalam', 'Lemari', 'Kasur'])
            ->map(fn (string $name): Facility => Facility::firstOrCreate(
                ['tenant_id' => $branch->tenant_id, 'branch_id' => $branch->id, 'name' => $name],
                ['status' => CommonStatus::Active],
            ));

        $types = [
            ['name' => 'Standard', 'capacity' => 1, 'monthly_price' => 800_000],
            ['name' => 'Deluxe', 'capacity' => 1, 'monthly_price' => 1_200_000],
            ['name' => 'Suite', 'capacity' => 2, 'monthly_price' => 2_000_000],
        ];

        $roomNumber = 1;

        foreach ($types as $type) {
            $roomType = RoomType::firstOrCreate(
                ['tenant_id' => $branch->tenant_id, 'branch_id' => $branch->id, 'name' => $type['name']],
                [
                    'capacity' => $type['capacity'],
                    'monthly_price' => $type['monthly_price'],
                    'status' => CommonStatus::Active,
                ],
            );

            for ($i = 0; $i < 3; $i++) {
                $room = Room::firstOrCreate(
                    ['branch_id' => $branch->id, 'number' => str_pad((string) $roomNumber, 3, '0', STR_PAD_LEFT)],
                    [
                        'tenant_id' => $branch->tenant_id,
                        'room_type_id' => $roomType->id,
                        'floor' => (int) ceil($roomNumber / 3),
                        'status' => CommonStatus::Active,
                        'room_status' => RoomStatus::Available,
                    ],
                );

                $room->facilities()->syncWithoutDetaching($facilities->random(3)->pluck('id'));
                $roomNumber++;
            }
        }
    }
}
