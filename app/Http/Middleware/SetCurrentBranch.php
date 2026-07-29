<?php

namespace App\Http\Middleware;

use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentBranch
{
    public function __construct(private BranchContext $context) {}

    /**
     * Resolve the active branch for staff users from the session, validating it
     * against the branches the user is allowed to access and falling back to the
     * first accessible branch.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isStaff()) {
            $accessible = $user->accessibleBranches();

            $desiredId = $request->session()->get('current_branch_id');
            $current = $accessible->firstWhere('id', $desiredId) ?? $accessible->first();

            if ($current) {
                $request->session()->put('current_branch_id', $current->id);
            } else {
                $request->session()->forget('current_branch_id');
            }

            $this->context->set($current, $accessible);
        }

        return $next($request);
    }
}
