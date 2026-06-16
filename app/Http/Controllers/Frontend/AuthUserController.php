<?php

namespace App\Http\Controllers\Frontend;
use App\Rules\Captcha;
use App\Models\UserModel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\Authuser\LoginRequest;
use App\Http\Requests\Frontend\Authuser\RegisterRequest;
use App\Http\Requests\Frontend\Authuser\ResetpassRequets;
use App\Mail\RegisterMail;
use App\Mail\ResetPassword;
use App\Models\ContactModel as Contact;
use App\Models\PromotionModel as Promotion;
use App\Models\FaqModel as Faq;
use App\Models\CateNewsModel as CateNews;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AuthUserController extends Controller
{
    public function __construct()
    {
        $slide = Promotion::where('cate_slide_id',1)->where('promotion_hidden',1)->get();
        $contact = Contact::where('contact_hidden',1)->limit(1)->get();
        $faq = Faq::where('faq_hidden',1)->where('faq_about',0)->orderBy('faq_id','desc')->get();
        $cateNews = CateNews::where('cate_news_hidden',1)
        -> orderBy('cate_news_sort', 'asc')
        -> get();
        view()->share(compact('slide','contact','faq','cateNews'));
    }

    public function login()
    {
        return view('frontend.pages.account.login');
    }

    public function loginPost(LoginRequest $request)
    {
        if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
            // Đăng nhập thành công
            if ($request->has('remember')) {
                Cookie::queue('email', $request->email, 1440);
                Cookie::queue('password', $request->password, 1440);
            } else {
                Cookie::queue('email', "");
                Cookie::queue('password', "");
            }
            // Reset biến đếm đăng nhập sai
            session()->forget('login_attempts');
            return redirect(route('home.page'));
        } else {
            // Đăng nhập thất bại, tăng biến đếm đăng nhập sai
            $loginAttempts = session('login_attempts', 0) + 1;
            session(['login_attempts' => $loginAttempts]);
            Session::flash('iconMessage','error');
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

        Mail::to($user->email)->send(new RegisterMail($user));
        Session::flash('iconMessage','success');
        return redirect(route('user.login'))->with('message', 'Đăng ký thành công mời bạn đăng nhập');
    }

    public function verifiedRegister($user)
    {
        $user = UserModel::find($user);
        $user->email_verified_at = Carbon::now();
        $user->save();
        Session::flash('iconMessage','success');
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

    public function forgotPost(Request $request)
    {
        $user = UserModel::getUsersingle($request->email);
        $mailDate = Carbon::now('Asia/Ho_Chi_Minh');

        if(!empty($user)){
            $user->remember_token = Str::random(30);
            $user->save();
            Mail::to($user->email)->send(new ResetPassword($user, $mailDate));
            Session::flash('iconMessage','success');
            return redirect()->back()->with('message','Đã gửi mail');
        } else {
            Session::flash('iconMessage','error');
            return redirect()->back()->with('message','Email không tồn tại');
        }
    }
    
    public function resetPass($remember_token)
    {
        $user = UserModel::getTokenSingle($remember_token);
        if(!empty($user)){
            $data['user'] = $user;
            return view('frontend.pages.account.reset_password',$data);
        } else {
            abort(404);
        }
    }

    function resetPassPost($token, ResetpassRequets $request)
    {
        $user = UserModel::getTokenSingle($token);
        $user->password = Hash::make($request->password);
        $user->remember_token = Str::random(30);
        $user->save();
        Session::flash('iconMessage','success');
        return redirect(route('user.login'))->with('message','Đổi mật khẩu thành công');
    }
}