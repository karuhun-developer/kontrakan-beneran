<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Redirect users to the correct home after a two-factor challenge, matching
     * the role-based routing used for a standard login.
     */
    public function toResponse($request): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();
        $home = $user && ! $user->isStaff()
            ? route('portal.dashboard')
            : route('dashboard');

        return redirect()->intended($home);
    }
}
