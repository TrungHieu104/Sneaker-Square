<?php

namespace App\Actions;

use App\Exceptions\SocialSignInRefused;
use App\Models\UserModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;

/**
 * Finds or makes the account behind a Google or Facebook profile.
 */
class SignInWithProviderAction
{
    /**
     * @param  array{column: string, label: string, stateless: bool}  $provider
     *
     * @throws SocialSignInRefused
     */
    public function execute(array $provider, ProviderUser $profile): UserModel
    {
        $column = $provider['column'];
        $email = (string) $profile->getEmail();

        if ($email !== '' && UserModel::where('email', $email)->whereNull($column)->exists()) {
            throw new SocialSignInRefused('Email này đã được đăng ký bằng phương thức khác.');
        }

        $user = UserModel::where($column, $profile->getId())->first();

        if ($user) {
            // Only the name is refreshed; an avatar the customer set themselves stays.
            $user->name = $profile->getName();
            $user->save();

            return $user;
        }

        if ($email === '') {
            throw new SocialSignInRefused('Tài khoản '.$provider['label'].' của bạn không chia sẻ email. Vui lòng cho phép chia sẻ email hoặc đăng ký bằng email.');
        }

        if (UserModel::where('email', $email)->exists()) {
            throw new SocialSignInRefused('Email này đã gắn với một tài khoản '.$provider['label'].' khác. Vui lòng đăng nhập bằng đúng tài khoản đó, hoặc dùng "Quên mật khẩu" để đặt mật khẩu cho email này.');
        }

        return UserModel::create([
            'name' => $profile->getName(),
            'email' => $email,
            $column => $profile->getId(),
            'user_img' => $profile->getAvatar(),
            // Random, so the account cannot be entered through the password
            // form until the customer sets one themselves.
            'password' => Hash::make(Str::random(40)),
        ]);
    }
}
