<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\RoomTypeRequest;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class RoomTypeController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(): Response
    {
        $branch = $this->currentBranch();

        $roomTypes = RoomType::forBranch($branch->id)
            ->withCount('rooms')
            ->latest()
            ->paginate(15)
            ->through(fn (RoomType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'capacity' => $type->capacity,
                'monthly_price' => $type->monthly_price,
                'rooms_count' => $type->rooms_count,
                'status' => $type->status->value,
                'status_label' => $type->status->label(),
            ]);

        return Inertia::render('master/room-types/index', [
            'roomTypes' => $roomTypes,
        ]);
    }

    public function create(): Modal
    {
        return Inertia::modal('master/room-types/form')
            ->baseRoute('room-types.index');
    }

    public function store(RoomTypeRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        RoomType::create([
            ...$request->validated(),
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
        ]);

        return to_route('room-types.index')->with('success', 'Tipe kamar berhasil ditambahkan.');
    }

    public function edit(RoomType $roomType): Modal
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $roomType->branch_id);

        return Inertia::modal('master/room-types/form', [
            'roomType' => [
                'id' => $roomType->id,
                'name' => $roomType->name,
                'description' => $roomType->description,
                'capacity' => $roomType->capacity,
                'monthly_price' => $roomType->monthly_price,
                'status' => $roomType->status->value,
            ],
        ])->baseRoute('room-types.index');
    }

    public function update(RoomTypeRequest $request, RoomType $roomType): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $roomType->branch_id);

        $roomType->update($request->validated());

        return to_route('room-types.index')->with('success', 'Tipe kamar berhasil diperbarui.');
    }

    public function destroy(RoomType $roomType): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $roomType->branch_id);

        $roomType->delete();

        return to_route('room-types.index')->with('success', 'Tipe kamar berhasil dihapus.');
    }
}
