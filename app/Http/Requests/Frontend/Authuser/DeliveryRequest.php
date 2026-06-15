<?php

namespace App\Http\Requests\Frontend\Authuser;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryRequest extends FormRequest
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
            'info_name' => 'required','max:30',
            'info_phone' => 'required','min:10','max:11',
            'info_email' => 'required|email|ends_with:@gmail.com',
            'info_address' => 'required',
            'info_province' => 'required',
            'info_district' => 'required',
            'info_ward' => 'required',
            'infoService' => 'required'
        ];
    }
    public function messages()
    {
        return [
            'info_name.required' => 'Bạn chưa nhập họ và tên',
            'info_name.max' => 'Họ và tên quá dài',
            'info_phone.min' => 'Số điện thoại phải dài hơn 10 số',
            'info_phone.max' => 'Số điện thoại quá dài',
            'info_phone.required'=> 'Bạn chưa nhập số điện thoại',
            'info_email.required' => 'Bạn chưa nhập email',
            'info_email.ends_with' => 'Email sai định dạng',
            'info_email.email' => 'Nhập email chưa đúng',
            'info_address.required' => 'Bạn chưa nhập địa chỉ giao hàng',
            'info_province.required' => 'Vui lòng chọn Tỉnh, Thành phố',
            'info_district.required' => 'Vui lòng chọn Quận, Huyện',
            'info_ward.required' => 'Vui lòng chọn Phường, Xã',
            'infoService.required' => 'Vui lòng chọn đơn vị vận chuyển',  
        ];
    }
}