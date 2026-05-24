<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CateNewsUpdateRequest extends FormRequest
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
            'name' => ['required','min:3',Rule::unique('cate_news', 'cate_news_name')->ignore( request()->id,'cate_news_id')],
            'slug' => ['required','min:3',Rule::unique('cate_news', 'cate_news_slug')->ignore( request()->id,'cate_news_id')],
            'sort' => ['required','min:0', 'max:100000000', 'numeric','integer'],
            'img_blog' => ['image']
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập tên danh mục bài viết!',
            'name.min' => 'Tên danh mục quá ngắn',
            'name.unique' => 'Tên danh mục đã tồn tại, vui lòng nhập tên danh mục mới',
            'slug.required' => 'Vui lòng nhập đường dẫn danh mục bài viết!',
            'slug.min' => 'Tên danh mục quá ngắn',
            'slug.unique' => 'Đường dẫn danh mục đã tồn tại, vui lòng nhập tên danh mục mới',
            'sort.required' => 'Vui lòng chọn số thứ tự danh mục!',
            'sort.min' => 'Vui lòng nhập thứ tự lớn hơn 0!',
            'sort.max' => 'Số thứ tự quá lớn!',
            'sort.numeric' => 'Số thứ tự không được nhập chữ!',
            'sort.integer' => 'Vui lòng nhập số nguyên!',
            'img_blog.image' => 'File không được hỗ trợ!',
        ];
    }

}