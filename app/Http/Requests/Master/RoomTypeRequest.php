<?php

namespace App\Http\Requests\Master;

use App\Enums\CommonStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RoomTypeRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'monthly_price' => ['required', 'integer', 'min:0'],
            'status' => ['required', new Enum(CommonStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama tipe kamar',
            'capacity' => 'kapasitas',
            'monthly_price' => 'harga bulanan',
        ];
    }
}
