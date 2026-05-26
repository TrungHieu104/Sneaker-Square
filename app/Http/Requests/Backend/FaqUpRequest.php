<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class FaqUpRequest extends FormRequest
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
            'name' => ['required','min:3',Rule::unique('faq', 'faq_name')->ignore( request()->id,'faq_id')],
            'content' => ['required','min:3'],
        ];
    }
    public function messages(){
        return [
            'name.required' => 'Vui lòng nhập tiêu đề!',
            'name.min' => 'Tiêu đề quá ngắn!',
            'name.unique' => 'Tiêu đề đã tồn tại!',
            'content.required' => 'Vui lòng nhập nội dung!',
            'content.min' => 'Nội dung quá ngắn!',
        ];
    }
}
