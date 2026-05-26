<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagsUpdateRequest extends FormRequest
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
            'contentTg' => ['required','min:3', Rule::unique('tags', 'tag_content')->ignore( request()->id,'tag_id')],
            'slug' => ['required','min:3', Rule::unique('tags', 'tag_slug')->ignore( request()->id,'tag_id')],

        ];
    }

    public function messages()
    {
        return [
            'contentTg.required' => 'Vui lòng nhập nội dung tag bài viết!',
            'contentTg.min' => 'Nội dung tag quá ngắn',
            'contentTg.unique' => 'Nội dung tag đã tồn tại, vui lòng nhập nội dung tag mới',
            'slug.required' => "Vui lòng nhập đường dẫn thẻ tag!",
            'slug.min'=> "Đường dẫn thẻ quá ngắn!",
            'slug.max' => "Đường dẫn thẻ quá dài!",
            'slug.unique' => "Nội dung thẻ đã có, vui lòng nhập đường dẫn mới!",
        ];
    }
}
