<?php

namespace Database\Factories;

use App\Enums\CommonStatus;
use App\Models\Branch;
use App\Models\RoomType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement(['Standard', 'Deluxe', 'Suite', 'Ekonomi']),
            'description' => fake()->optional()->sentence(),
            'capacity' => fake()->numberBetween(1, 3),
            'monthly_price' => fake()->numberBetween(500, 3000) * 1000,
            'status' => CommonStatus::Active,
        ];
    }

    /**
     * Attach the room type to an existing branch (and its tenant).
     */
    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
        ]);
    }
}
