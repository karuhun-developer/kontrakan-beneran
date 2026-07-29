<?php

namespace App\Http\Requests\Laundry;

use App\Enums\LaundryPaymentSource;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LaundryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchId = app(BranchContext::class)->currentId();

        return [
            'penyewa_id' => [
                'required',
                Rule::exists('penyewas', 'id')->where('branch_id', $branchId),
            ],
            'payment_source' => ['required', Rule::enum(LaundryPaymentSource::class)],
            'weight' => ['nullable', 'numeric', 'min:0.1', 'max:999.99'],
            'price' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'penyewa_id' => 'penyewa',
            'payment_source' => 'sumber pembayaran',
            'weight' => 'berat',
            'price' => 'harga',
        ];
    }
}
