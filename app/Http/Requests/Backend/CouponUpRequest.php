<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class CouponUpRequest extends FormRequest
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
            'name' => ['required','min:3', 'max:50',Rule::unique('coupon', 'coupon_name')->ignore( request()->id,'coupon_id')],
            'code' => ['required','max:50','regex:/[A-Z]/','min:3',Rule::unique('coupon', 'coupon_code')->ignore( request()->id,'coupon_id')],
            'start_coupon' => ['required'],
            'end_coupon' => ['required','after:start_coupon'],
            'quantity' => ['required','min:0', 'max:100000000', 'numeric','integer'],
            'money' => ['required','min:0', 'numeric','integer'],
        ];
    }  
    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập tiêu đề khuyến mãi!',
            'name.min' => 'Tiêu đề quá ngắn',
            'name.max' => 'Tiêu đề quá dài',
            'name.unique' => 'Tiêu đề đã tồn tại!',
            'code.required' => 'Vui lòng nhập mã khuyến mãi!',
            'code.regex' => 'Mã khuyến mãi viết chữ in hoa!',
            'code.max' => 'Mã khuyến mãi quá dài!',
            'code.min' => 'Mã khuyến mãi quá ngắn!',
            'code.unique' => 'Mã khuyến mãi đã tồn tại!',
            'start_coupon.required' => 'Vui lòng chọn ngày bắt đầu khuyến mãi!',
            'end_coupon.required' => 'Vui lòng chọn ngày kết thúc khuyến mãi!',
            'end_coupon.after' => 'Ngày kết thúc phải sau ngày bắt đầu!',
            'quantity.required' => 'Vui lòng nhập số lượng!',
            'quantity.min' => 'Vui lòng nhập số lượng lớn hơn 0!',
            'quantity.max' => 'Số lượng mã quá lớn!',
            'quantity.numeric' => 'Số lượng mã không được nhập chữ!',
            'quantity.integer' => 'Vui lòng nhập số nguyên',
            'money.min' => 'Vui lòng nhập giá trị lớn hơn 0',
            'money.required' => 'Vui lòng nhập giá trị!',
            'money.numeric' => 'Giá trị giảm không được nhập chữ!',
            'money.integer' => 'Vui lòng nhập số nguyên',
        ];
    }
}
