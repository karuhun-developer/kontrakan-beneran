<?php

namespace Database\Factories;

use App\Enums\CommonStatus;
use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->city();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'Cabang '.$name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'status' => CommonStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommonStatus::Inactive,
        ]);
    }
}
