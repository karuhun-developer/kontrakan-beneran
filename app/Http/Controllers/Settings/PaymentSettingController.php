<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\ResolvesCurrentBranch;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingController extends Controller
{
    use ResolvesCurrentBranch;

    public function edit(): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('payment-settings/index', [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'bank_name' => $branch->bank_name,
                'bank_account_number' => $branch->bank_account_number,
                'bank_account_holder' => $branch->bank_account_holder,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $branch = $this->currentBranch();

        $validated = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_holder' => ['nullable', 'string', 'max:150'],
        ], [], [
            'bank_name' => 'nama bank',
            'bank_account_number' => 'nomor rekening',
            'bank_account_holder' => 'atas nama',
        ]);

        $branch->update($validated);

        return back()->with('success', 'Informasi rekening berhasil disimpan.');
    }
}
