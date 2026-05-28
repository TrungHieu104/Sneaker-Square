<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
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
            'username' => ['required','min:5', 'max:50', 'unique:users'],
            'email' => 'required|email|ends_with:@gmail.com|unique:users',
            'passwords' => 'required|min:8|max:30|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            'confirm-pass' => 'required|min:8|same:passwords',
        ];
    }
    public function messages(){
        return [
            'name.required' => 'Vui lòng nhập họ tên!',
            'name.min' => 'Họ tên quá ngắn!',
            'name.max' => 'Họ tên quá dài!',
            'username.required' => 'Vui lòng nhập tên đăng nhập!',
            'username.min' => 'Tên đăng nhập quá ngắn!',
            'username.max' => 'Tên đăng nhập quá dài!',
            'username.unique' => 'Tên đăng nhập đã tồn tại!',
            'email.required' => 'Bạn chưa nhập email!',
            'email.unique' => "Email đã được đăng ký!",
            'email.email' => 'Email nhập không đúng định dạng!',
            'email.ends_with' => 'Email phải có đuôi là @gmail.com!',
            'passwords.required' => 'Vui lòng nhập mật khẩu!',
            'passwords.min' => 'Mật khẩu ít nhất 8 kí tự!',
            'passwords.max' => 'Mật khẩu tối đa 30 kí tự!',
            'passwords.regex' => 'Mật khẩu bao gồm chữ thường, hoa và ít nhất một kí tự đặc biệt!',
            'confirm-pass.required' => 'Vui lòng nhập lại mật khẩu!',
            'confirm-pass.min'=> 'Mật khẩu ít nhất 8 kí tự',
            'confirm-pass.same'=> 'Hai mật khẩu không khớp!',
        ];
    }
}
