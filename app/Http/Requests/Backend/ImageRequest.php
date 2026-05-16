<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class ImageRequest extends FormRequest
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
            'img_name' => ['required'],
            'img_name.*' => ['image'],
        ];
    }

    public function messages() {
        return [
            'img_name.required' => 'Vui lòng chọn file hình ảnh cho sản phẩm!',
            'img_name.*' => 'File không được hỗ trợ!',
        ];
    }
}
