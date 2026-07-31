<?php

namespace App\Actions\Fortify;

use App\Actions\Tenancy\ProvisionOwnerAccount;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly ProvisionOwnerAccount $provisionOwner) {}

    /**
     * Validate and create a newly registered user. Self-registration makes the
     * user the owner of a fresh tenant with default branch/room data.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $this->provisionOwner->provision($user);

        return $user;
    }
}
