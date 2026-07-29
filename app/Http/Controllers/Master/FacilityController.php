<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\FacilityRequest;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class FacilityController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(): Response
    {
        $branch = $this->currentBranch();

        $facilities = Facility::forBranch($branch->id)
            ->latest()
            ->paginate(15)
            ->through(fn (Facility $facility): array => [
                'id' => $facility->id,
                'name' => $facility->name,
                'description' => $facility->description,
                'status' => $facility->status->value,
                'status_label' => $facility->status->label(),
            ]);

        return Inertia::render('master/facilities/index', [
            'facilities' => $facilities,
        ]);
    }

    public function create(): Modal
    {
        return Inertia::modal('master/facilities/form')
            ->baseRoute('facilities.index');
    }

    public function store(FacilityRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        Facility::create([
            ...$request->validated(),
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
        ]);

        return to_route('facilities.index')->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function edit(Facility $facility): Modal
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $facility->branch_id);

        return Inertia::modal('master/facilities/form', [
            'facility' => [
                'id' => $facility->id,
                'name' => $facility->name,
                'description' => $facility->description,
                'status' => $facility->status->value,
            ],
        ])->baseRoute('facilities.index');
    }

    public function update(FacilityRequest $request, Facility $facility): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $facility->branch_id);

        $facility->update($request->validated());

        return to_route('facilities.index')->with('success', 'Fasilitas berhasil diperbarui.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $facility->branch_id);

        $facility->delete();

        return to_route('facilities.index')->with('success', 'Fasilitas berhasil dihapus.');
    }
}
