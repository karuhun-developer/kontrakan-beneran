<?php

namespace App\Http\Requests\FrontDesk;

use App\Enums\BookingStatus;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class BookingRequest extends FormRequest
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
            'room_id' => [
                'required',
                Rule::exists('rooms', 'id')->where('branch_id', $branchId),
            ],
            'penyewa_id' => [
                'required',
                Rule::exists('penyewas', 'id')->where('branch_id', $branchId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['nullable', new Enum(BookingStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'room_id' => 'kamar',
            'penyewa_id' => 'penyewa',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
        ];
    }
}
