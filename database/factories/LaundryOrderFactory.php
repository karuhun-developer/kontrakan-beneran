<?php

namespace Database\Factories;

use App\Enums\LaundryPaymentSource;
use App\Enums\LaundryStatus;
use App\Models\Branch;
use App\Models\LaundryOrder;
use App\Models\Penyewa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaundryOrder>
 */
class LaundryOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weight = fake()->numberBetween(2, 10);

        return [
            'branch_id' => Branch::factory(),
            'tenant_id' => fn (array $attributes) => Branch::find($attributes['branch_id'])?->tenant_id,
            'penyewa_id' => fn (array $attributes) => Penyewa::factory()->create([
                'branch_id' => $attributes['branch_id'],
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'recorded_by' => null,
            'deposit_transaction_id' => null,
            'invoice_id' => null,
            'number' => fn (): string => 'LDR-'.fake()->unique()->numberBetween(100000, 999999),
            'status' => LaundryStatus::Received,
            'payment_source' => LaundryPaymentSource::Invoice,
            'weight' => $weight,
            'price' => $weight * 7000,
            'ordered_on' => now()->toDateString(),
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

    public function status(LaundryStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
