<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class InfoPassUpRequest extends FormRequest
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
            'password' => ['required','same:same_password'],
            'new-pass' => ['required','min:8','max:30','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'],
        ];
    }
    public function messages(){
        return [
            'password.required' => 'Vui lòng nhập mật khẩu hiện tại!',
            'password.same' => 'Mật khẩu hiện tại không đúng!',
            'new-pass.min' => 'Mật khẩu ít nhất 8 kí tự!',
            'new-pass.required' => 'Vui lòng nhập mật khẩu mới!',
            'new-pass.max' => 'Mật khẩu tối đa 30 kí tự!',
            'new-pass.regex' => 'Mật khẩu bao gồm chữ thường, hoa và ít nhất một kí tự đặc biệt!',
        ];
    }
}
