<?php

namespace App\Http\Requests\Backend;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CateSlideUpdate extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'min:3', 'max:50', Rule::unique('cate_slide', 'cate_slide_name')->ignore(request()->id, 'cate_slide_id')],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập tiêu đề!',
            'name.min' => 'Tiêu đề quá ngắn!',
            'name.max' => 'Tên quá dài!',
            'name.unique' => 'Tên danh mục đã tồn tại!',
        ];
    }
}
