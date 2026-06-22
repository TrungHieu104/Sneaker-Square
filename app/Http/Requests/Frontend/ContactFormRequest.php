<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
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
            'email' => 'required|email|ends_with:@gmail.com',
            'title' => ['required','max:255'],
            'content' => ['required']
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Bạn chưa nhập họ và tên',
            'name.min' => 'Họ và tên quá ngắn',
            'name.max' => 'Họ và tên quá dài',
            'email.required' => 'Bạn chưa nhập email',
            'email.ends_with' => 'Bạn nhập email sai định dạng',
            'email.email' => 'Nhập email chưa đúng',
            'title.required' => 'Vui lòng nhập tiêu đề',
            'title.max' => 'Tiêu đề quá dài',
            'content.required' => 'Vui lòng nhập nội dung',
        ];
    }
}
