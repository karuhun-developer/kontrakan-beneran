<?php

namespace App\Http\Requests\Management;

use App\Enums\CommonStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Owner credentials are only required when creating a new business;
     * an existing tenant already has its owner account.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isCreate = $this->route('tenant') === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', new Enum(CommonStatus::class)],
            'owner_name' => [Rule::requiredIf($isCreate), 'nullable', 'string', 'max:255'],
            'owner_email' => [Rule::requiredIf($isCreate), 'nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'owner_password' => [Rule::requiredIf($isCreate), 'nullable', 'string', 'min:8'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama bisnis',
            'owner_name' => 'nama owner',
            'owner_email' => 'email owner',
            'owner_password' => 'password owner',
        ];
    }
}
