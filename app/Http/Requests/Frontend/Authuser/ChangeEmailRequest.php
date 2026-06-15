<?php

namespace App\Http\Requests\Frontend\Authuser;

use Illuminate\Foundation\Http\FormRequest;

class ChangeEmailRequest extends FormRequest
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
             'email' => 'required|email|ends_with:@gmail.com|unique:users,email',
        ];
    }
    public function messages()
    {
        return [
            'email.unique' => 'Email này đã tồn tại',
            'email.required' => 'Bạn chưa nhập email',
            'email.ends_with' => 'Bạn nhập email sai định dạng',
            'email.email' => 'Nhập email chưa đúng',
        ];
    }
}
