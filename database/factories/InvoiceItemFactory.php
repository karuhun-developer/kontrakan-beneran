<?php

namespace Database\Factories;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(100, 2000) * 1000;

        return [
            'invoice_id' => Invoice::factory(),
            'type' => InvoiceItemType::Rent,
            'description' => 'Sewa kamar',
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'amount' => $unitPrice,
        ];
    }
}
