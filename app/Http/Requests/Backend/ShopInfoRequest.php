<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class ShopInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^\d{8,11}$/'],
            'email' => ['required', 'email', 'max:255'],
            'map_embed' => ['nullable', 'string', 'max:5000'],
            'fanpage_embed' => ['nullable', 'string', 'max:5000'],
            'chat_embed' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address.required' => 'Vui lòng nhập địa chỉ cửa hàng.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại chỉ gồm chữ số, từ 8 đến 11 số, không có khoảng trắng.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            '*.max' => 'Nội dung quá dài (tối đa :max ký tự).',
        ];
    }
}
