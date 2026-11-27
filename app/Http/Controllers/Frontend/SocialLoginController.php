<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\SignInWithProviderAction;
use App\Exceptions\SocialSignInRefused;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
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
        ],
        'facebook' => [
            'column' => 'facebook_id',
            'label' => 'Facebook',
            'stateless' => true,
        ],
    ];

    public function redirect(string $provider): ProviderRedirect
    {
        return $this->driver($provider)->redirect();
    }

    public function callback(string $provider, SignInWithProviderAction $signIn): RedirectResponse
    {
        $config = self::PROVIDERS[$provider];

        try {
            Auth::login($signIn->execute($config, $this->driver($provider)->user()));

            return redirect()->intended(route('home.page'));
        } catch (SocialSignInRefused $e) {
            return $this->backToLogin($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->backToLogin('Không đăng nhập được bằng '.$config['label'].', vui lòng thử lại.');
        }
    }

    private function backToLogin(string $message): RedirectResponse
    {
        Session::flash('iconMessage', 'error');

        return redirect()->route('user.login')->with('message', $message);
    }

    private function driver(string $provider): AbstractProvider
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return self::PROVIDERS[$provider]['stateless'] ? $driver->stateless() : $driver;
    }
}
