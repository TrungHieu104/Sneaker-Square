<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class TagsRequest extends FormRequest
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
            'contentTg' => ['required', 'min:1', 'max:100','unique:mysql.tags,tag_content'],
            'slug' => ['required', 'min:1', 'max:100','unique:mysql.tags,tag_slug']
        ];
    }

    public function messages()
    {
        return [
            'contentTg.required' => "Vui lòng nhập nội dung thẻ tag!",
            'contentTg.min'=> "Nội dung thẻ quá ngắn!",
            'contentTg.max' => "Nội dung thẻ quá dài!",
            'contentTg.unique' => "Nội dung thẻ đã có, vui lòng nhập nội dung mới!",
            'slug.required' => "Vui lòng nhập đường dẫn thẻ tag!",
            'slug.min'=> "Đường dẫn thẻ quá ngắn!",
            'slug.max' => "Đường dẫn thẻ quá dài!",
            'slug.unique' => "Nội dung thẻ đã có, vui lòng nhập đường dẫn mới!",
        ];
    }
}
