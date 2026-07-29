<?php

namespace App\Http\Requests\Billing;

use App\Enums\CashflowType;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CashflowRequest extends FormRequest
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
            'type' => ['required', new Enum(CashflowType::class)],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
            'occurred_on' => ['required', 'date'],
            'cashflow_category_id' => [
                'nullable',
                Rule::exists('cashflow_categories', 'id')
                    ->where('branch_id', $branchId)
                    ->where('type', $this->input('type')),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'jumlah',
            'description' => 'deskripsi',
            'occurred_on' => 'tanggal',
            'cashflow_category_id' => 'kategori',
        ];
    }
}
