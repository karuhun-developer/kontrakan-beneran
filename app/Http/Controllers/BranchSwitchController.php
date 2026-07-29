<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BranchSwitchController extends Controller
{
    /**
     * Switch the active branch for the authenticated staff user.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
        ]);

        $branch = Branch::findOrFail($validated['branch_id']);

        if (! $request->user()->canAccessBranch($branch)) {
            throw ValidationException::withMessages([
                'branch_id' => 'Anda tidak memiliki akses ke cabang ini.',
            ]);
        }

        $request->session()->put('current_branch_id', $branch->id);

        return back();
    }
}
