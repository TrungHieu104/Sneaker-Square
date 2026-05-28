<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserInfoUpRequest extends FormRequest
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
            'name' => ['required','min:3', 'max:50'],
            'username' => ['required','min:5', 'max:50',Rule::unique('users', 'username')->ignore( request()->id,'user_id')],
            'email' => ['required','email','ends_with:@gmail.com',Rule::unique('users', 'email')->ignore( request()->id,'user_id')],
        ];
    }
    public function messages(){
        return [
            'name.required' => 'Vui lòng nhập họ tên!',
            'name.max' => 'Tên quá dài!',
            'name.min' => 'Tên quá ngắn!',
            'username.required' => 'Vui lòng nhập tên đăng nhập!',
            'username.min' => 'Tên đăng nhập quá ngắn!',
            'username.unique' => 'Tên đăng nhập đã tồn tại!',
            'username.max' => 'Tên đăng nhập quá dài!',
            'email.required' => 'Bạn chưa nhập email!',
            'email.unique' => "Email đã được đăng ký!",
            'email.email' => 'Email nhập không đúng định dạng!',
            'email.ends_with' => 'Email phải có đuôi là @gmail.com!',

        ];
    }
}
