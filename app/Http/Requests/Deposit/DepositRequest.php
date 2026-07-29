<?php

namespace App\Http\Requests\Deposit;

use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepositRequest extends FormRequest
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
            'booking_id' => [
                'nullable',
                Rule::exists('bookings', 'id')->where('branch_id', $branchId),
            ],
            'amount' => ['required', 'integer', 'min:1'],
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
            'amount' => 'nominal',
        ];
    }
}
