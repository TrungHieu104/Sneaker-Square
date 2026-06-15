<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryInfoRequest extends FormRequest
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
            'info_name' => ['required','min:3', 'max:50'],
            'info_address' => ['required','min:3', 'max:50'],
            'info_email' => 'required|email|ends_with:@gmail.com',
            'info_phone' => ['required','min:10','max:11','numeric'],
            'info_province' => 'required',
            'info_district' => 'required',
            'info_ward' => 'required',
            'info_delivery_fee' => 'required'
        ];
    }
    public function messages()
    {
        return [
            'info_name.required' => 'Vui lòng nhập tên',
            'info_name.min' => 'Nhập tên từ 3 - 50 ký tự',
            'info_name.max' => 'Nhập tên từ 3 - 50 ký tự',
            'info_address.required' => 'Vui lòng nhập địa chỉ',
            'info_address.max' => 'Nhập tên từ 3 - 50 ký tự',
            'info_address.min' => 'Nhập tên từ 3 - 50 ký tự',
            'info_email.required' => 'Vui lòng nhập email',
            'info_email.email' => 'Nhập sai định dạng email',
            'info_email.ends_with' => 'Gmail phải kết thúc bằng "@gmail.com"',
            'info_phone.required' => 'Vui lòng nhập số điện thoại',
            'info_phone.min' => 'Nhập số điện thoại từ 10 - 11 ký tự',
            'info_phone.max' => 'Nhập số điện thoại từ 10 - 11 ký tự',
            'info_phone.numeric' => 'Phải nhập số',
            'info_province.required' => 'Vui lòng chọn Tỉnh, Thành phố',
            'info_district.required' => 'Vui lòng chọn Quận, Huyện',
            'info_ward.required' => 'Vui lòng chọn Phường, Xã',
            'info_delivery_fee.required' => 'Vui lòng chọn đơn vị vận chuyển'
        ];
    }
}