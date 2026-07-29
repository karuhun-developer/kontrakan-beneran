<?php

namespace App\Actions\Portal;

use App\Enums\UserRole;
use App\Models\Penyewa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProvisionPortalAccount
{
    /**
     * Create or update the login account a penyewa uses for the portal.
     * A staff member provisions this, so the email is treated as verified.
     * Re-running for an existing account resets its email/password.
     *
     * A blank password falls back to the system default (config portal.default_password),
     * which drives the "change your password" banner on the portal.
     */
    public function handle(Penyewa $penyewa, string $email, ?string $password = null): User
    {
        $plainPassword = filled($password) ? $password : config('portal.default_password');

        return DB::transaction(function () use ($penyewa, $email, $plainPassword): User {
            $user = $penyewa->user;

            if ($user === null) {
                $user = new User(['tenant_id' => $penyewa->tenant_id]);
                $user->email_verified_at = now();
            }

            $user->name = $penyewa->name;
            $user->email = $email;
            $user->password = $plainPassword;
            $user->save();

            if (! $user->hasRole(UserRole::Penyewa->value)) {
                $user->assignRole(UserRole::Penyewa->value);
            }

            if ($penyewa->user_id !== $user->id) {
                $penyewa->user()->associate($user);
                $penyewa->save();
            }

            return $user;
        });
    }
}
