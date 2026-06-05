<?php

namespace App\Actions\Fortify;

use App\Http\Requests\Auth\ResetUserPasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make(
            $input,
            ResetUserPasswordRequest::baseRules(),
            [],
            ResetUserPasswordRequest::attributeNames(),
        )->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
