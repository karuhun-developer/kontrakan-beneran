<?php

namespace Database\Factories;

use App\Enums\CashflowType;
use App\Models\Branch;
use App\Models\Cashflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cashflow>
 */
class CashflowFactory extends Factory
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
            'cashflow_category_id' => null,
            'recorded_by' => null,
            'type' => CashflowType::Expense,
            'amount' => fake()->numberBetween(50, 2000) * 1000,
            'description' => fake()->sentence(3),
            'occurred_on' => now()->toDateString(),
            'notes' => null,
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
