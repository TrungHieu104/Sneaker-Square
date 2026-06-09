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
            'g-recaptcha-response' => new Captcha(),		            
        ];
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