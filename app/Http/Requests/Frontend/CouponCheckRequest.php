<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class CouponCheckRequest extends FormRequest
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
            'coupon' => ['required','regex:/[A-Z]/','min:3','max:20'],
        ];
    }
    public function messages()
    {
        return [
            'coupon.required' => 'Vui lòng nhập mã giảm giá',
            'coupon.min' => 'Mã giảm giá quá ngắn',
            'coupon.max' => 'Mã giảm giá quá dài',
            'coupon.regex' => 'Vui lòng nhập in hoa mã giảm giá',
            
        ];
    }
}
