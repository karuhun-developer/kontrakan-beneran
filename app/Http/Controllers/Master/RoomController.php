<?php

namespace App\Http\Controllers\Master;

use App\Enums\RoomStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\RoomRequest;
use App\Models\Branch;
use App\Models\Facility;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class RoomController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(): Response
    {
        $branch = $this->currentBranch();

        $rooms = Room::forBranch($branch->id)
            ->with('roomType:id,name')
            ->orderBy('floor')
            ->orderBy('number')
            ->paginate(15)
            ->through(fn (Room $room): array => [
                'id' => $room->id,
                'number' => $room->number,
                'name' => $room->name,
                'floor' => $room->floor,
                'room_type' => $room->roomType?->name,
                'monthly_price' => $room->effective_monthly_price,
                'status' => $room->status->value,
                'status_label' => $room->status->label(),
                'room_status' => $room->room_status->value,
                'room_status_label' => $room->room_status->label(),
                'room_status_color' => $room->room_status->color(),
            ]);

        return Inertia::render('master/rooms/index', [
            'rooms' => $rooms,
        ]);
    }

    public function create(): Modal
    {
        $branch = $this->currentBranch();

        return Inertia::modal('master/rooms/form', $this->formOptions($branch))
            ->baseRoute('rooms.index');
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        $room = Room::create([
            ...$request->safe()->except('facilities'),
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
        ]);

        $room->facilities()->sync($request->validated('facilities', []));

        return to_route('rooms.index')->with('success', 'Kamar berhasil ditambahkan.');
    }

    public function edit(Room $room): Modal
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $room->branch_id);

        return Inertia::modal('master/rooms/form', [
            ...$this->formOptions($branch),
            'room' => [
                'id' => $room->id,
                'room_type_id' => $room->room_type_id,
                'number' => $room->number,
                'name' => $room->name,
                'floor' => $room->floor,
                'description' => $room->description,
                'monthly_price' => $room->monthly_price,
                'status' => $room->status->value,
                'room_status' => $room->room_status->value,
                'facilities' => $room->facilities()->pluck('facilities.id'),
            ],
        ])->baseRoute('rooms.index');
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $room->branch_id);

        $room->update($request->safe()->except('facilities'));
        $room->facilities()->sync($request->validated('facilities', []));

        return to_route('rooms.index')->with('success', 'Kamar berhasil diperbarui.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $room->branch_id);

        $room->delete();

        return to_route('rooms.index')->with('success', 'Kamar berhasil dihapus.');
    }

    /**
     * Shared select options for the room form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(Branch $branch): array
    {
        return [
            'roomTypeOptions' => RoomType::forBranch($branch->id)
                ->orderBy('name')
                ->get(['id', 'name', 'monthly_price'])
                ->map(fn (RoomType $type): array => [
                    'value' => $type->id,
                    'label' => $type->name,
                    'monthly_price' => $type->monthly_price,
                ]),
            'facilityOptions' => Facility::forBranch($branch->id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Facility $facility): array => [
                    'value' => $facility->id,
                    'label' => $facility->name,
                ]),
            'roomStatusOptions' => collect(RoomStatus::cases())
                ->map(fn (RoomStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ]),
        ];
    }
}
