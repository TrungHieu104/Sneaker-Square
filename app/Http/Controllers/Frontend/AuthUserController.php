<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Http\Requests\Frontend\Authuser\LoginRequest;
use App\Http\Requests\Frontend\Authuser\RegisterRequest;
use App\Http\Requests\Frontend\Authuser\ResetpassRequets;
use App\Mail\RegisterMail;
use App\Mail\ResetPassword;
use App\Models\CateNewsModel as CateNews;
use App\Models\UserModel;
use App\Services\PasswordResetLinks;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class AuthUserController extends Controller
{
    use SharesStorefrontLayout;

    public function __construct()
    {
        $this->shareStorefrontLayout();
        $cateNews = CateNews::withCount('getNewsInCate')->where('cate_news_hidden', 1)
            ->orderBy('cate_news_sort', 'asc')
            ->get();
        view()->share(compact('cateNews'));
    }

    public function login()
    {
        return view('frontend.pages.account.login');
    }

    public function loginPost(LoginRequest $request)
    {
        $remember = $request->boolean('remember');

        // "Remember me" is Laravel's own long-lived token now. It used to write
        // the customer's password into a cookie for 24 hours and print it back
        // into the form's value attribute — the password itself was the
        // remember-me token, sitting in the browser in readable form.
        if (auth()->attempt(
            ['email' => $request->input('email'), 'password' => $request->input('password')],
            $remember
        )) {
            // The address is safe to keep so the field comes back filled in.
            $remember
                ? Cookie::queue('email', $request->input('email'), 1440)
                : Cookie::queue(Cookie::forget('email'));

            // Clears the password cookie left behind by the previous scheme.
            Cookie::queue(Cookie::forget('password'));

            // Reset the failed-attempt counter.
            session()->forget('login_attempts');

            return redirect(route('home.page'));
        } else {
            // Sign-in failed; bump the failed-attempt counter.
            $loginAttempts = session('login_attempts', 0) + 1;
            session(['login_attempts' => $loginAttempts]);
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Tài khoản hoặc mật khẩu không chính xác.');
        }
    }

    public function register()
    {
        return view('frontend.pages.account.register');
    }

    public function registerPost(RegisterRequest $request)
    {
        $user = new UserModel;
        $user->name = trim(strip_tags($request['name']));
        $user->password = trim(strip_tags($request['password']));
        $user->email = trim(strip_tags($request['email']));
        $user->save();

        Mail::to($user->email)->send(new RegisterMail($user, RegisterMail::verifyLink($user)));
        Session::flash('iconMessage', 'success');

        return redirect(route('user.login'))->with('message', 'Đăng ký thành công mời bạn đăng nhập');
    }

    public function verifiedRegister($user)
    {
        $user = UserModel::findOrFail($user);
        $user->email_verified_at = Carbon::now();
        $user->save();
        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Xác nhận email thành công');
    }

    public function logout()
    {
        auth()->guard('web')->logout();

        return redirect(route('user.login'));
    }

    public function forgot()
    {
        return view('frontend.pages.account.forgot');
    }

    public function forgotPost(Request $request, PasswordResetLinks $links)
    {
        $user = UserModel::getUsersingle($request->email);
        $mailDate = Carbon::now();

        if (! empty($user)) {
            $link = route('user.reset_pass', ['token' => $links->issue($user), 'email' => $user->email]);
            Mail::to($user->email)->send(new ResetPassword($user, $mailDate, $link));
            Session::flash('iconMessage', 'success');

            return redirect()->back()->with('message', 'Đã gửi mail');
        } else {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', 'Email không tồn tại');
        }
    }

    public function resetPass(Request $request, PasswordResetLinks $links, string $token)
    {
        $user = UserModel::getUsersingle((string) $request->query('email'));
        abort_unless($links->valid($user, $token), 404);

        return view('frontend.pages.account.reset_password', ['token' => $token, 'email' => $user->email]);
    }

    public function resetPassPost(ResetpassRequets $request, PasswordResetLinks $links, string $token)
    {
        $user = UserModel::getUsersingle((string) $request->input('email'));

        if (! $links->valid($user, $token)) {
            Session::flash('iconMessage', 'error');

            return redirect(route('user.forgot'))->with('message', 'Link đặt lại mật khẩu đã hết hạn, vui lòng gửi lại yêu cầu.');
        }

        $links->setPassword($user, (string) $request->password);
        Session::flash('iconMessage', 'success');

        return redirect(route('user.login'))->with('message', 'Đổi mật khẩu thành công');
    }
}
