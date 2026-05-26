<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsUpdateRequest extends FormRequest
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
            'title'=> ['required', 'min:10','max:200',Rule::unique('news', 'news_title')->ignore( request()->id,'news_id')],
            'slug'=> ['required', 'min:10','max:200',Rule::unique('news', 'news_slug')->ignore( request()->id,'news_id')],
            'summarize'=> ['required','min:10','max:400'],
            'content'=>['required','min:10','max:90000'],
            'img_blog' => ['image']
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề!',
            'title.min' => 'Tiêu đề quá ngắn',
            'title.max' => 'Tiều đề quá dài',
            'title.unique' => 'Tiêu đề đã tồn tại, vui lòng nhập tiêu đề mới',
            'slug.required' => 'Vui lòng nhập đường dẫn!',
            'slug.min' => 'Đường dẫn quá ngắn',
            'slug.max' => 'Đường dẫn quá dài',
            'slug.unique' => 'Tiêu đề đã tồn tại, vui lòng nhập đường dẫn mới',
            'summarize.required' => 'Vui lòng nhập tóm tắt bài viết!',
            'summarize.min' => 'Tóm tắt bài viết quá ngắn',
            'summarize.max' => 'Tóm tắt bài viết quá dài',
            'content.required' => 'Nội dung bài viết không được để trống!',
            'content.min' => 'Nội dung bài viết quá ngắn',
            'content.max' => 'Nội dung bài viết quá dài',
            'img_blog.image' => 'File không được hỗ trợ!',
        ];
    }
}