<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class ProductQuantityRequest extends FormRequest
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
            'pro_id' => ['required'],
            'quantity_date' => ['required'],
            'size_id' => ['nullable'],
            'color_id' => ['nullable'],
        ];
    }

    public function messages() {
        return [
            'pro_id.required' => 'Vui lòng chọn sản phẩm!',
            
            'quantity_date.required' => 'Vui lòng nhập ngày nhập hàng!',
        ]; 
    }
}
