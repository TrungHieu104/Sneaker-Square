<?php

namespace App\Http\Requests\Backend;

use App\Services\ShopSettings;
use Illuminate\Foundation\Http\FormRequest;

class CartCouponOfferRequest extends FormRequest
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
        $rules = [];

        foreach (['coupon_percent', 'coupon_days'] as $field) {
            [$min, $max] = ShopSettings::CART_REMINDER_LIMITS[$field];
            $rules[$field] = ['required', 'integer', 'min:'.$min, 'max:'.$max];
        }

        $rules['min_order'] = ['required', 'integer', 'min:0', 'max:1000000000'];
        $rules['max_discount'] = ['nullable', 'integer', 'min:1000', 'max:1000000000'];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required' => 'Vui lòng nhập giá trị.',
            '*.integer' => 'Giá trị phải là số nguyên.',
            'coupon_percent.min' => 'Mức giảm tối thiểu :min%.',
            'coupon_percent.max' => 'Mức giảm tối đa :max%.',
            'coupon_days.min' => 'Hiệu lực tối thiểu :min ngày.',
            'coupon_days.max' => 'Hiệu lực tối đa :max ngày.',
            'min_order.min' => 'Đơn tối thiểu không được âm.',
            'min_order.max' => 'Đơn tối thiểu quá lớn.',
            'max_discount.min' => 'Giảm tối đa ít nhất 1.000đ.',
            'max_discount.max' => 'Giảm tối đa quá lớn.',
        ];
    }
}
