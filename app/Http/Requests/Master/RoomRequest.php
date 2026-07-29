<?php

namespace App\Http\Requests\Master;

use App\Enums\CommonStatus;
use App\Enums\RoomStatus;
use App\Models\Room;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class RoomRequest extends FormRequest
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
        $room = $this->route('room');

        return [
            'room_type_id' => [
                'required',
                Rule::exists('room_types', 'id')->where('branch_id', $branchId),
            ],
            'number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'number')
                    ->where('branch_id', $branchId)
                    ->ignore($room instanceof Room ? $room->id : null),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'floor' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', new Enum(CommonStatus::class)],
            'room_status' => ['required', new Enum(RoomStatus::class)],
            'facilities' => ['array'],
            'facilities.*' => [
                Rule::exists('facilities', 'id')->where('branch_id', $branchId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'room_type_id' => 'tipe kamar',
            'number' => 'nomor kamar',
            'floor' => 'lantai',
            'monthly_price' => 'harga bulanan',
            'room_status' => 'status kamar',
        ];
    }
}
