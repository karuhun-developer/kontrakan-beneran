<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
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
            'notificationFeed' => $this->notificationsFor($user),
            'portal' => $this->portalStateFor($user),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Portal-only flags. A penyewa still using the system default password
     * is nudged to change it.
     *
     * @return array{must_change_password: bool}|null
     */
    private function portalStateFor(?User $user): ?array
    {
        if (! $user || $user->isStaff()) {
            return null;
        }

        return [
            'must_change_password' => Hash::check((string) config('portal.default_password'), $user->password),
        ];
    }

    /**
     * Unread count plus the most recent notifications for the header bell.
     * Shared by both shells — the admin bell and the portal bell each resolve
     * their own links from this same feed. Guests get nothing.
     *
     * @return array{unread_count: int, recent: list<array<string, mixed>>}
     */
    private function notificationsFor(?User $user): array
    {
        if (! $user) {
            return ['unread_count' => 0, 'recent' => []];
        }

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'recent' => $user->notifications()
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }
}
