<?php

namespace App\Http\Requests\Master;

use App\Enums\CommonStatus;
use App\Models\Penyewa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class PenyewaRequest extends FormRequest
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
        $penyewa = $this->route('penyewa');
        $ignoreUserId = $penyewa instanceof Penyewa ? $penyewa->user_id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreUserId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'id_card_number' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female'],
            'address' => ['nullable', 'string'],
            'status' => ['required', new Enum(CommonStatus::class)],
            'id_card' => ['nullable', 'image', 'max:4096'],
            'deposit_amount' => ['nullable', 'integer', 'min:1'],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama penyewa',
            'id_card_number' => 'nomor KTP',
            'id_card' => 'foto KTP',
            'deposit_amount' => 'nominal deposit',
            'password' => 'kata sandi',
        ];
    }
}
