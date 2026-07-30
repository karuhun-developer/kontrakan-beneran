<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\User;
use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentBranch
{
    public function __construct(private BranchContext $context) {}

    /**
     * Route-name prefixes that must stay reachable even when the user has no
     * active branch, so a branchless owner can still create their first branch,
     * switch context, tweak account settings, or log out.
     *
     * @var list<string>
     */
    private const ACCESSIBLE_WITHOUT_BRANCH = [
        'management.branches.',
        'branch.switch',
        'portal.',
        'logout',
        'profile.',
        'appearance.',
        'password.',
        'user-password.',
        'two-factor.',
    ];

    /**
     * Resolve the active branch for staff users from the session, validating it
     * against the branches the user is allowed to access and falling back to the
     * first accessible branch. A branch manager (owner) without any branch is
     * pushed to the branch management page so they create their first one before
     * anything else can be scoped.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isStaff()) {
            $accessible = $user->accessibleBranches();
            $current = $this->resolveCurrentBranch($request, $user, $accessible);

            $this->context->set($current, $accessible);

            if ($current === null
                && ! $user->isSuperAdmin()
                && $user->can('branch.manage')
                && $this->shouldForceBranchSetup($request)
            ) {
                return redirect()
                    ->route('management.branches.index')
                    ->with('warning', 'Belum ada cabang. Tambahkan cabang terlebih dahulu untuk mulai mengelola kost.');
            }
        }

        return $next($request);
    }

    /**
     * Pick the active branch from the session, defaulting to the first
     * accessible branch. Super admins may opt into a cross-branch overview
     * ("Semua Cabang"), in which case no single branch is active.
     *
     * @param  Collection<int, Branch>  $accessible
     */
    private function resolveCurrentBranch(Request $request, User $user, Collection $accessible): ?Branch
    {
        if ($user->isSuperAdmin() && $request->session()->get('current_branch_all')) {
            return null;
        }

        $desiredId = $request->session()->get('current_branch_id');
        $current = $accessible->firstWhere('id', $desiredId) ?? $accessible->first();

        if ($current) {
            $request->session()->put('current_branch_id', $current->id);
        } else {
            $request->session()->forget('current_branch_id');
        }

        return $current;
    }

    /**
     * Whether a branchless request should be redirected to the branch setup page.
     */
    private function shouldForceBranchSetup(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $routeName = $request->route()?->getName() ?? '';

        if ($routeName === '') {
            return false;
        }

        foreach (self::ACCESSIBLE_WITHOUT_BRANCH as $allowed) {
            if (str_starts_with($routeName, $allowed)) {
                return false;
            }
        }

        return true;
    }
}
