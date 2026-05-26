<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class MenuUpRequest extends FormRequest
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
            'name' => ['required',Rule::unique('menu', 'menu_name')->ignore( request()->id,'menu_id')],
            'position' => ['required','min:0', 'max:100000000', 'numeric','integer',Rule::unique('menu', 'menu_position')->ignore( request()->id,'menu_id')],
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
