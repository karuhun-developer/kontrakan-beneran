<?php

namespace App\Http\Controllers\Master;

use App\Actions\Deposit\OpenDeposit;
use App\Actions\Portal\ProvisionPortalAccount;
use App\Enums\CommonStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\PenyewaRequest;
use App\Models\Penyewa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
                'has_account' => $penyewa->user_id !== null,
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

    public function store(PenyewaRequest $request, OpenDeposit $openDeposit, ProvisionPortalAccount $provisionAccount): RedirectResponse
    {
        $branch = $this->currentBranch();

        $penyewa = Penyewa::create([
            ...$request->safe()->except('id_card', 'deposit_amount', 'password'),
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

        // A penyewa with an email gets a portal login; a blank password uses the default.
        if (filled($penyewa->email)) {
            $provisionAccount->handle($penyewa, $penyewa->email, $request->validated('password'));
        }

        return to_route('penyewa.index')->with('success', 'Penyewa berhasil ditambahkan.');
    }

    /**
     * Quick-create a penyewa from the booking form (front desk shortcut).
     * Gated by booking.manage so staff without penyewa.manage can still use it.
     */
    public function quickStore(Request $request, OpenDeposit $openDeposit, ProvisionPortalAccount $provisionAccount): JsonResponse
    {
        $branch = $this->currentBranch();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:male,female'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['nullable', 'string', 'min:8'],
            'deposit_amount' => ['nullable', 'integer', 'min:1'],
        ], [], ['password' => 'kata sandi']);

        $penyewa = Penyewa::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'email' => $validated['email'] ?? null,
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            'status' => CommonStatus::Active,
        ]);

        $depositAmount = (int) ($validated['deposit_amount'] ?? 0);

        if ($depositAmount > 0) {
            $openDeposit->handle(penyewa: $penyewa, amount: $depositAmount, actor: $request->user());
        }

        // A penyewa with an email gets a portal login; a blank password uses the default.
        if (filled($penyewa->email)) {
            $provisionAccount->handle($penyewa, $penyewa->email, $validated['password'] ?? null);
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

        $penyewa->loadMissing('user:id,email');

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
                'has_account' => $penyewa->user_id !== null,
                'account_email' => $penyewa->user?->email ?? $penyewa->email,
            ],
        ])->baseRoute('penyewa.index');
    }

    /**
     * Provision (or reset) the penyewa's portal login credentials.
     */
    public function account(Request $request, Penyewa $penyewa, ProvisionPortalAccount $action): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $penyewa->branch_id);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($penyewa->user_id)],
            'password' => ['nullable', 'string', 'min:8'],
        ], [], ['email' => 'email', 'password' => 'kata sandi']);

        $action->handle($penyewa, $validated['email'], $validated['password'] ?? null);

        return to_route('penyewa.index')->with('success', 'Akun portal penyewa berhasil disimpan.');
    }

    public function update(PenyewaRequest $request, Penyewa $penyewa): RedirectResponse
    {
        $branch = $this->currentBranch();
        $this->authorizeBranch($branch, $penyewa->branch_id);

        $penyewa->update($request->safe()->except('id_card', 'password'));

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
