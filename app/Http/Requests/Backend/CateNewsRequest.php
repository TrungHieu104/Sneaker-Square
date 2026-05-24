<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class CateNewsRequest extends FormRequest
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
            'name' => ['required','min:3', 'max:50','unique:mysql.cate_news,cate_news_name'],
            'slug' => ['required','min:3', 'max:50','unique:mysql.cate_news,cate_news_slug'],
            'sort' => ['required','min:0', 'max:100000000', 'numeric','integer'],
            'img_blog' => ['image']
        ];
    }
    public function messages(){
        return [
            'name.required' => 'Vui lòng nhập tên danh mục!',
            'name.min' => 'Tên danh mục quá ngắn!',
            'name.max' => 'Tên danh mục quá dài!',
            'name.unique' => 'Tên danh mục bài viết đã có, vui lòng nhập tên mới!',
            'slug.required' => 'Vui lòng nhập đường dẫn danh mục!',
            'slug.min' => 'Đường dẫn danh mục quá ngắn!',
            'slug.max' => 'Đường dẫn danh mục quá dài!',
            'slug.unique' => 'Đường dẫn danh mục bài viết đã có, vui lòng nhập đường dẫn mới!',
            'sort.required' => 'Vui lòng nhập số thứ tự!',
            'sort.min' => 'Vui lòng nhập thứ tự lớn hơn 0!',
            'sort.max' => 'Số thứ tự quá lớn!',
            'sort.numeric' => 'Số thứ tự không được nhập chữ!',
            'sort.integer' => 'Vui lòng nhập số nguyên!',
            'img_blog.image' => 'File không được hỗ trợ!',
        ];
    }
}