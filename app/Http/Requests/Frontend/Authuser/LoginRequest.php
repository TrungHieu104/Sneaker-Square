<?php

namespace App\Http\Requests\Frontend\Authuser;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Captcha;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|ends_with:@gmail.com',
            'password' => [
                'required',
                Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
            // A rule object is skipped when the field is absent, so listing the
            // Captcha rule on its own left the check entirely up to the client:
            // omit the field and there was nothing to fail. Once the widget is
            // on the page the token has to come with it.
            'g-recaptcha-response' => self::captchaRequired()
                ? ['required', new Captcha()]
                : ['nullable', new Captcha()],
        ];
    }
    
    /**
     * Whether this sign-in has to carry a reCAPTCHA token.
     *
     * Kept as a static so the login view can ask the same question and show the
     * widget exactly when the rule expects it.
     */
    public static function captchaRequired(): bool
    {
        return session('login_attempts', 0) > (int) config('services.captcha.after_failed_attempts', 3);
    }

    public function messages()
    {
        return [
            'email.required' => 'Bạn chưa nhập email',
            'email.email' => 'Nhập email chưa đúng',
            'email.ends_with' => 'Email nhập chưa đúng định dạng',
            'password.required' => 'Bạn chưa nhập mật khẩu',
            'password.min' => 'Mật khẩu từ 8 ký tự trở lên',
            'password.mixed' => 'Mật khẩu phải có chữ in hoa',
            'password.numbers' => 'Mật khẩu phải có số',
            'password.symbols' => 'Mật khẩu phải có ký tự đặc biệt',
        ];
    }
}