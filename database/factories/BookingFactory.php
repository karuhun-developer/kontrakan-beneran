<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');
        $end = (clone $start)->modify('+1 month');

        return [
            'room_id' => Room::factory(),
            'tenant_id' => fn (array $attributes) => Room::find($attributes['room_id'])?->tenant_id,
            'branch_id' => fn (array $attributes) => Room::find($attributes['room_id'])?->branch_id,
            'penyewa_id' => fn (array $attributes) => Penyewa::factory()->state([
                'tenant_id' => Room::find($attributes['room_id'])?->tenant_id,
                'branch_id' => Room::find($attributes['room_id'])?->branch_id,
            ]),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'monthly_price' => fake()->numberBetween(500, 3000) * 1000,
            'status' => BookingStatus::Confirmed,
            'notes' => null,
            'checked_in_at' => null,
            'checked_out_at' => null,
        ];
    }

    /**
     * Place the booking in a specific branch, reusing (or creating) a room there.
     */
    public function forBranch(Branch $branch): static
    {
        return $this->state(function (array $attributes) use ($branch): array {
            $room = Room::query()
                ->forBranch($branch->id)
                ->inRandomOrder()
                ->first()
                ?? Room::factory()->forBranch($branch)->create();

            $penyewa = Penyewa::factory()->forBranch($branch)->create();

            return [
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'room_id' => $room->id,
                'penyewa_id' => $penyewa->id,
            ];
        });
    }

    /**
     * Book a specific room over an explicit date range.
     */
    public function forRoom(Room $room, string $start, string $end): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $room->tenant_id,
            'branch_id' => $room->branch_id,
            'room_id' => $room->id,
            'penyewa_id' => Penyewa::factory()->state([
                'tenant_id' => $room->tenant_id,
                'branch_id' => $room->branch_id,
            ]),
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
