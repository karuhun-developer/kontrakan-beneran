<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Redirect users to the correct home based on their role: penyewa go to the
     * tenant portal, staff go to the admin dashboard.
     */
    public function toResponse($request): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        return redirect()->intended($this->home($request));
    }

    private function home($request): string
    {
        $user = $request->user();

        if ($user && ! $user->isStaff()) {
            return route('portal.dashboard');
        }

        return route('dashboard');
    }
}
