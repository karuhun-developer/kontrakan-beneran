<?php

namespace Database\Factories;

use App\Enums\CommonStatus;
use App\Enums\RoomStatus;
use App\Models\Branch;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'tenant_id' => fn (array $attributes) => RoomType::find($attributes['room_type_id'])?->tenant_id,
            'branch_id' => fn (array $attributes) => RoomType::find($attributes['room_type_id'])?->branch_id,
            'number' => fake()->unique()->numerify('##'),
            'name' => null,
            'floor' => fake()->numberBetween(1, 3),
            'description' => fake()->optional()->sentence(),
            'monthly_price' => null,
            'status' => CommonStatus::Active,
            'room_status' => RoomStatus::Available,
        ];
    }

    /**
     * Place the room in a specific branch, reusing (or creating) a room type there.
     */
    public function forBranch(Branch $branch): static
    {
        return $this->state(function (array $attributes) use ($branch): array {
            $roomType = RoomType::query()
                ->forBranch($branch->id)
                ->inRandomOrder()
                ->first()
                ?? RoomType::factory()->forBranch($branch)->create();

            return [
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'room_type_id' => $roomType->id,
            ];
        });
    }

    public function occupied(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_status' => RoomStatus::Occupied,
        ]);
    }
}
