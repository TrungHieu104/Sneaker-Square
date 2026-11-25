<?php

namespace App\Services;

use App\Models\UserModel;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * One-time links to set a new password, for the forgotten password and for
 * the change asked from the account page. Tokens live hashed in
 * password_reset_tokens and expire after auth.passwords.users.expire
 * minutes. Asking for a link leaves remember_token alone; only setting the
 * new password rotates it.
 */
class PasswordResetLinks
{
    public function issue(UserModel $user): string
    {
        return Password::broker()->createToken($user);
    }

    public function valid(?UserModel $user, ?string $token): bool
    {
        return $user !== null && $token !== null && $token !== '' && Password::broker()->tokenExists($user, $token);
    }

    /**
     * Sets the new password and spends the link. A fresh remember_token logs
     * out every "remember me" cookie issued before, which may be how the
     * account was taken over in the first place.
     */
    public function setPassword(UserModel $user, string $password): void
    {
        $user->password = $password;
        $user->setRememberToken(Str::random(60));
        $user->save();

        Password::broker()->deleteToken($user);
    }
}
