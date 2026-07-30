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

        // Sentinel 0 → super admins switch to the cross-branch overview.
        if ($validated['branch_id'] === 0) {
            abort_unless($request->user()->isSuperAdmin(), 403);

            $request->session()->put('current_branch_all', true);
            $request->session()->forget('current_branch_id');

            return back();
        }

        $branch = Branch::findOrFail($validated['branch_id']);

        if (! $request->user()->canAccessBranch($branch)) {
            throw ValidationException::withMessages([
                'branch_id' => 'Anda tidak memiliki akses ke cabang ini.',
            ]);
        }

        $request->session()->forget('current_branch_all');
        $request->session()->put('current_branch_id', $branch->id);

        return back();
    }
}
