<?php

namespace App\Http\Requests\Frontend\Authuser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'min:5', 'max:30'],
            'email' => 'required|email|ends_with:@gmail.com|unique:users,email',
            'password' => [
                'required',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                'same:same_password',
            ],
            'same_password' => 'required|min:8',
            'terms' => 'accepted'
        ];
    }
    
    public function messages()
    {
        return [
            'name.required' => 'Bạn chưa nhập họ và tên',
            'name.min' => 'Họ và tên quá ngắn',
            'name.max' => 'Họ và tên quá dài',
            'email.unique' => 'Email này đã tồn tại',
            'email.required' => 'Bạn chưa nhập email',
            'email.ends_with' => 'Bạn nhập email sai định dạng',
            'email.email' => 'Nhập email chưa đúng',
            'password.same' => 'Mật khẩu không khớp',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu từ 8 ký tự trở lên',
            'password.mixed' => 'Mật khẩu phải có chữ in hoa',
            'password.numbers' => 'Mật khẩu phải có số',
            'password.symbols' => 'Mật khẩu phải có ký tự đặc biệt',
            'same_password.required' => 'Bạn chưa nhập lại mật khẩu',
            'same_password.min' => 'Mật khẩu nhập lại cùng từ 8 ký tự trở lên',
            'terms.accepted' => 'Vui lòng chấp nhận điều khoản & chính sách'
        ];
    }
}