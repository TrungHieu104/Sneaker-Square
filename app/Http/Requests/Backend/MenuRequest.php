<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class MenuRequest extends FormRequest
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
            'name' => ['required','unique:menu,menu_name'],
            'position' => ['required','min:0', 'max:100000000', 'numeric','integer','unique:menu,menu_position'],
            'slug' => ['required']
        ];
    }
    public function messages(){
        return [
            'name.required' => 'Vui lòng nhập tiêu đề!',
            'name.unique' => 'Tiêu đề đã tồn tại!',
            'position.required' => 'Vui lòng nhập số thứ tự!',
            'slug.required' => 'Vui lòng nhập đường dẫn!',
            'position.min' => 'Vui lòng nhập số thứ tự lớn hơn 0!',
            'position.max' => 'Số thứ tự quá lớn!',
            'position.numeric' => 'Số thứ tự không được nhập chữ!',
            'position.integer' => 'Vui lòng nhập số nguyên',
            'position.unique' => 'Số thứ tự đã tồn tại!',
        ];
    }
}
