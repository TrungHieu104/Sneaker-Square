<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ColorRequest extends FormRequest
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
            'color' => ['required', 'max:50', Rule::unique('color', 'color')->ignore(request()->id, 'color_id')],
            'color_vn' => ['required', 'max:50', Rule::unique('color', 'color_vn')->ignore(request()->id, 'color_id')],
        ];
    }

    public function messages(){
        return [
            'color.required' => 'Vui lòng nhập tên màu bằng tiếng Anh!',
            'color.unique' => 'Màu đã tồn tại!',
            'color.max' => 'Tên màu quá dài!',

            'color_vn.required' => 'Vui lòng nhập tên màu bằng tiếng Việt!',
            'color_vn.unique' => 'Màu đã tồn tại!',
            'color_vn.max' => 'Tên màu quá dài!',
        ];
    }
}
