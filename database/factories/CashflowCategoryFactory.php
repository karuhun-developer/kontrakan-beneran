<?php

namespace Database\Factories;

use App\Enums\CashflowType;
use App\Enums\CommonStatus;
use App\Models\Branch;
use App\Models\CashflowCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashflowCategory>
 */
class CashflowCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'tenant_id' => fn (array $attributes) => Branch::find($attributes['branch_id'])?->tenant_id,
            'name' => fake()->unique()->words(2, true),
            'type' => CashflowType::Expense,
            'is_system' => false,
            'status' => CommonStatus::Active,
        ];
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
        ]);
    }

    public function type(CashflowType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
