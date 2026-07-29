<?php

namespace Database\Factories;

use App\Enums\DepositStatus;
use App\Models\Branch;
use App\Models\Deposit;
use App\Models\Penyewa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->numberBetween(300, 3000) * 1000;

        return [
            'branch_id' => Branch::factory(),
            'tenant_id' => fn (array $attributes) => Branch::find($attributes['branch_id'])?->tenant_id,
            'penyewa_id' => fn (array $attributes) => Penyewa::factory()->create([
                'branch_id' => $attributes['branch_id'],
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'booking_id' => null,
            'amount' => $amount,
            'balance' => $amount,
            'status' => DepositStatus::Held,
            'held_at' => now()->toDateString(),
            'settled_at' => null,
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
}
