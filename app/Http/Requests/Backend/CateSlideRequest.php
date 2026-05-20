<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class CateSlideRequest extends FormRequest
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
            'name' => ['required','min:3', 'max:50','unique:cate_slide,cate_slide_name'],
        ];
    }
    public function messages(){
        return [
            'name.required' => 'Vui lòng nhập tiêu đề!',
            'name.min' => 'Tiêu đề quá ngắn!',
            'name.max' => 'Tên quá dài!',
            'name.unique' => 'Tiêu đề đã tồn tại!',
        ];
    }
}
