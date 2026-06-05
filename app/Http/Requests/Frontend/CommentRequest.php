<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
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
            'comment_content' => ['required', 'min:5'],
            'comment_name' => ['nullable', 'min:5', 'max:255'],
            'comment_email' => ['nullable', 'min:5', 'max:255', 'email', 'ends_with:@gmail.com'],
        ];
    }

    public function messages(){
        return [
            'comment_content.required' => 'Vui lòng nhập nội dung bình luận!',
            'comment_content.min' => 'Nội dung bình luận quá ngắn!',
            'comment_name.min' => 'Họ tên quá ngắn!',
            'comment_name.max' => 'Họ tên quá dài!',
            'comment_email.min' => 'Email quá ngắn!',
            'comment_email.max' => 'Email tên quá dài!',
            'comment_email.email' => 'Nhập email chưa đúng định dạng!',
            'comment_email.ends_with' => 'Email phải có đuôi là @gmail.com!',
        ];
    }
}
