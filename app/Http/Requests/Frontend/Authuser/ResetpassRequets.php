<?php

namespace App\Http\Requests\Frontend\Authuser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetpassRequets extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                'same:same_password',
            ],
            'same_password' => 'required|min:8',
        ];
    }
    
    public function messages()
    {
        return [
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu từ 8 ký tự trở lên',
            'password.mixed' => 'Mật khẩu phải có chữ in hoa',
            'password.numbers' => 'Mật khẩu phải có số',
            'password.symbols' => 'Mật khẩu phải có ký tự đặc biệt',
            'password.same' => 'Mật khẩu không khớp',
            'same_password.required' => 'Bạn chưa nhập lại mật khẩu',
            'same_password.min' => 'Mật khẩu nhập lại phải có ít nhất 8 ký tự',
        ];
    }
}