<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Penyewa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(500, 3000) * 1000;

        return [
            'penyewa_id' => Penyewa::factory(),
            'tenant_id' => fn (array $attributes) => Penyewa::find($attributes['penyewa_id'])?->tenant_id,
            'branch_id' => fn (array $attributes) => Penyewa::find($attributes['penyewa_id'])?->branch_id,
            'booking_id' => null,
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => $subtotal,
            'discount' => 0,
            'fine' => 0,
            'total' => $subtotal,
            'status' => InvoiceStatus::Unpaid,
            'notes' => null,
            'paid_at' => null,
        ];
    }

    /**
     * Place the invoice in a specific branch, reusing (or creating) a penyewa there.
     */
    public function forBranch(Branch $branch): static
    {
        return $this->state(function () use ($branch): array {
            $penyewa = Penyewa::factory()->forBranch($branch)->create();

            return [
                'tenant_id' => $branch->tenant_id,
                'branch_id' => $branch->id,
                'penyewa_id' => $penyewa->id,
            ];
        });
    }

    public function status(InvoiceStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
