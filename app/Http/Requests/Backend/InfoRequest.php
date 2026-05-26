<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class InfoRequest extends FormRequest
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
            'phone' => ['required','regex:/^(\+84|0)[0-9]{9,10}$/'],
            'email' => 'required|email|ends_with:@gmail.com',
            'address' => ['required','min:3','max:100','unique:contact,contact_address'],
        ];
    } 
    public function messages(){ return [
        'phone.required' => 'Bạn chưa nhập số điện thoại!',
        'phone.regex' => 'Số điện thoại không đúng định dạng!',
        'email.required' => 'Bạn chưa nhập email!',
        'email.email' => 'Email nhập không đúng định dạng!',
        'email.ends_with' => 'Email phải có đuôi @gmail.com',
        'address.required' => 'Bạn chưa nhập địa chỉ',
        'address.min' => 'Địa chỉ quá ngắn!',
        'address.max' => 'Địa chỉ quá dài!',
        'address.unique' => 'Địa chỉ đã tồn tại!',
    ];
    }
}
