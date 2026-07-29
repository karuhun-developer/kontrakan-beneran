<?php

namespace Database\Factories;

use App\Enums\DepositTransactionType;
use App\Models\Deposit;
use App\Models\DepositTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepositTransaction>
 */
class DepositTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deposit_id' => Deposit::factory(),
            'branch_id' => fn (array $attributes) => Deposit::find($attributes['deposit_id'])?->branch_id,
            'tenant_id' => fn (array $attributes) => Deposit::find($attributes['deposit_id'])?->tenant_id,
            'recorded_by' => null,
            'type' => DepositTransactionType::Hold,
            'amount' => fake()->numberBetween(100, 1000) * 1000,
            'reason' => null,
            'occurred_on' => now()->toDateString(),
            'notes' => null,
        ];
    }

    public function type(DepositTransactionType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
