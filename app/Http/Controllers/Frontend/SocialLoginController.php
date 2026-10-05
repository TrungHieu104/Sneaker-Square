<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\UserModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as ProviderRedirect;
use Throwable;

/**
 * Sign-in through Google or Facebook. The two differ only in the column that
 * remembers the account, whether the provider keeps state between the two
 * legs, and the wording shown when it fails.
 */
class SocialLoginController extends Controller
{
    private const PROVIDERS = [
        'google' => [
            'column' => 'google_id',
            'label' => 'Google',
            'stateless' => false,
            'email_taken' => 'Tài khoản Google đã tồn tại',
        ],
        'facebook' => [
            'column' => 'facebook_id',
            'label' => 'Facebook',
            'stateless' => true,
            'email_taken' => 'Email này đã được đăng ký bằng phương thức khác',
        ],
    ];

    public function redirect(string $provider): ProviderRedirect
    {
        return $this->driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $config = self::PROVIDERS[$provider];

        try {
            $profile = $this->driver($provider)->user();

            if (UserModel::where('email', $profile->getEmail())->whereNull($config['column'])->exists()) {
                Session::flash('iconMessage', 'error');

                return redirect(route('user.login'))->with('message', $config['email_taken']);
            }

            $user = UserModel::where($config['column'], $profile->getId())->first();

            if ($user) {
                // Only the name is refreshed; an avatar the customer set themselves stays.
                $user->name = $profile->getName();
                $user->save();
            } else {
                $user = UserModel::create([
                    'name' => $profile->getName(),
                    'email' => $profile->getEmail(),
                    $config['column'] => $profile->getId(),
                    'user_img' => $profile->getAvatar(),
                    // Random, so the account cannot be entered through the
                    // password form until the customer sets one themselves.
                    'password' => Hash::make(Str::random(40)),
                ]);
            }

            Auth::login($user);

            return redirect()->intended(route('home.page'));
        } catch (Throwable $e) {
            report($e);
            Session::flash('iconMessage', 'error');

            return redirect()->route('user.login')
                ->with('message', 'Không đăng nhập được bằng '.$config['label'].', vui lòng thử lại.');
        }
    }

    private function driver(string $provider): AbstractProvider
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return self::PROVIDERS[$provider]['stateless'] ? $driver->stateless() : $driver;
    }
}
