<?php

namespace App\Http\Middleware;

use App\Support\BranchContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(private BranchContext $branchContext) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'two_factor_enabled' => ! is_null($user->two_factor_confirmed_at),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'role' => $user->primaryRole()?->value,
                    'role_label' => $user->primaryRole()?->label(),
                    'is_staff' => $user->isStaff(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                ] : null,
            ],
            'tenant' => $user?->tenant
                ? ['id' => $user->tenant->id, 'name' => $user->tenant->name]
                : null,
            'branchContext' => [
                'current' => $this->branchContext->current()
                    ? ['id' => $this->branchContext->current()->id, 'name' => $this->branchContext->current()->name]
                    : null,
                'available' => $this->branchContext->accessible()
                    ->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->name])
                    ->values(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
