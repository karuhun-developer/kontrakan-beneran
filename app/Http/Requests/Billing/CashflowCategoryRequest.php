<?php

namespace App\Http\Requests\Billing;

use App\Enums\CashflowType;
use App\Enums\CommonStatus;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CashflowCategoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cashflow_categories', 'name')
                    ->where('branch_id', $branchId)
                    ->where('type', $this->input('type'))
                    ->ignore($this->route('cashflowCategory')),
            ],
            'type' => ['required', new Enum(CashflowType::class)],
            'status' => ['required', new Enum(CommonStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
        ];
    }
}
