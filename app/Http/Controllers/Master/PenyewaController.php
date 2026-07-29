<?php

namespace App\Http\Controllers\Master;

use App\Actions\Deposit\OpenDeposit;
use App\Enums\CommonStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\PenyewaRequest;
use App\Models\Penyewa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class PenyewaController extends Controller
{
    use ResolvesCurrentBranch;

    public function index(): Response
    {
        $branch = $this->currentBranch();

        $penyewas = Penyewa::forBranch($branch->id)
            ->latest()
            ->paginate(15)
            ->through(fn (Penyewa $penyewa): array => [
                'id' => $penyewa->id,
                'name' => $penyewa->name,
                'email' => $penyewa->email,
                'phone' => $penyewa->phone,
                'gender' => $penyewa->gender,
                'status' => $penyewa->status->value,
                'status_label' => $penyewa->status->label(),
                'id_card_url' => $penyewa->getFirstMediaUrl(Penyewa::MEDIA_ID_CARD, 'thumb') ?: null,
            ]);

        return Inertia::render('master/penyewa/index', [
            'penyewa' => $penyewas,
        ]);
    }

    public function create(): Modal
    {
        return Inertia::modal('master/penyewa/form')
            ->baseRoute('penyewa.index');
    }

    public function store(PenyewaRequest $request, OpenDeposit $openDeposit): RedirectResponse
    {
        $branch = $this->currentBranch();

        $penyewa = Penyewa::create([
            ...$request->safe()->except('id_card', 'deposit_amount'),
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
        ]);

        if ($request->hasFile('id_card')) {
            $penyewa->addMediaFromRequest('id_card')->toMediaCollection(Penyewa::MEDIA_ID_CARD);
        }

        $depositAmount = (int) $request->validated('deposit_amount', 0);

        if ($depositAmount > 0) {
            $openDeposit->handle(penyewa: $penyewa, amount: $depositAmount, actor: $request->user());
        }

        return to_route('penyewa.index')->with('success', 'Penyewa berhasil ditambahkan.');
    }

    /**
     * Quick-create a penyewa from the booking form (front desk shortcut).
     * Gated by booking.manage so staff without penyewa.manage can still use it.
     */
    public function quickStore(Request $request, OpenDeposit $openDeposit): JsonResponse
    {
        $branch = $this->currentBranch();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:male,female'],
            'deposit_amount' => ['nullable', 'integer', 'min:1'],
        ]);

        $penyewa = Penyewa::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'status' => CommonStatus::Active,
        ]);

        $depositAmount = (int) ($validated['deposit_amount'] ?? 0);

        if ($depositAmount > 0) {
            $openDeposit->handle(penyewa: $penyewa, amount: $depositAmount, actor: $request->user());
        }

        return response()->json([
            'id' => $penyewa->id,
            'name' => $penyewa->name,
        ]);
    }

    public function edit(Penyewa $penyewa): Modal
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $penyewa->branch_id);

        return Inertia::modal('master/penyewa/form', [
            'penyewa' => [
                'id' => $penyewa->id,
                'name' => $penyewa->name,
                'email' => $penyewa->email,
                'phone' => $penyewa->phone,
                'id_card_number' => $penyewa->id_card_number,
                'gender' => $penyewa->gender,
                'address' => $penyewa->address,
                'status' => $penyewa->status->value,
                'id_card_url' => $penyewa->getFirstMediaUrl(Penyewa::MEDIA_ID_CARD) ?: null,
            ],
        ])->baseRoute('penyewa.index');
    }

    public function update(PenyewaRequest $request, Penyewa $penyewa): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $penyewa->branch_id);

        $penyewa->update($request->safe()->except('id_card'));

        if ($request->hasFile('id_card')) {
            $penyewa->addMediaFromRequest('id_card')->toMediaCollection(Penyewa::MEDIA_ID_CARD);
        }

        return to_route('penyewa.index')->with('success', 'Penyewa berhasil diperbarui.');
    }

    public function destroy(Penyewa $penyewa): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $penyewa->branch_id);

        $penyewa->delete();

        return to_route('penyewa.index')->with('success', 'Penyewa berhasil dihapus.');
    }
}
