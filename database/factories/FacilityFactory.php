<?php

namespace Database\Factories;

use App\Enums\CommonStatus;
use App\Models\Branch;
use App\Models\Facility;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
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
            'name' => fake()->randomElement([
                'AC', 'Kamar Mandi Dalam', 'WiFi', 'Lemari', 'Kasur', 'Meja Belajar',
            ]),
            'description' => fake()->optional()->sentence(),
            'status' => CommonStatus::Active,
        ];
    }

    /**
     * Attach the facility to an existing branch (and its tenant).
     */
    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
        ]);
    }
}
