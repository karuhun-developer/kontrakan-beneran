<?php

namespace Database\Factories;

use App\Enums\CommonStatus;
use App\Models\Branch;
use App\Models\Penyewa;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penyewa>
 */
class PenyewaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $branch = Branch::factory();

        return [
            'tenant_id' => Tenant::factory(),
            'branch_id' => $branch,
            'user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'id_card_number' => fake()->numerify('################'),
            'gender' => fake()->randomElement(['male', 'female']),
            'address' => fake()->address(),
            'status' => CommonStatus::Active,
        ];
    }

    /**
     * Attach the penyewa to an existing branch (and its tenant).
     */
    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
        ]);
    }
}
