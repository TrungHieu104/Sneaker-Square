<?php

namespace App\Http\Requests\Frontend\Authuser;

use Illuminate\Foundation\Http\FormRequest;

class UserInfoRequest extends FormRequest
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
            'name' => ['required', 'min:5', 'max:30'],
            'user_phone' => ['required', 'regex:/^0[0-9]{9,10}$/'],
            'img__new' => ['image', 'mimes:jpeg,png,jpg', 'max:2048'], 
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Bạn chưa nhập họ và tên',
            'name.min' => 'Họ và tên quá ngắn',
            'name.max' => 'Họ và tên quá dài',
            'user_phone.regex' => 'Định dạng số điện thoại không đúng. Ví dụ: 0383144668',
            'user_phone.required' => 'Bạn chưa nhập số điện thoại',
            'img__new.image' => 'File phải là một hình ảnh',
            'img__new.mimes' => 'Định dạng được hỗ trợ: jpeg, png, jpg',
            'img__new.max' => 'Kích thước ảnh không được vượt quá 2MB',
        ];
    }
}