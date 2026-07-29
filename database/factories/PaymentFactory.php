<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'tenant_id' => fn (array $attributes) => Invoice::find($attributes['invoice_id'])?->tenant_id,
            'branch_id' => fn (array $attributes) => Invoice::find($attributes['invoice_id'])?->branch_id,
            'penyewa_id' => fn (array $attributes) => Invoice::find($attributes['invoice_id'])?->penyewa_id,
            'recorded_by' => null,
            'amount' => fn (array $attributes) => Invoice::find($attributes['invoice_id'])?->total ?? 500_000,
            'method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Completed,
            'reference' => null,
            'paid_at' => now(),
            'notes' => null,
        ];
    }

    public function method(PaymentMethod $method): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => $method,
        ]);
    }

    public function status(PaymentStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
