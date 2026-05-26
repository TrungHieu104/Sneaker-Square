<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class NewsRequest extends FormRequest
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
            'title'=> ['required', 'min:10','max:200','unique:mysql.news,news_title'],
            'slug'=> ['required', 'min:10','max:200','unique:mysql.news,news_slug'],
            'summarize'=> ['required','min:10','max:400'],
            'content'=>['required','min:10','max:90000'],
            'img_blog' => ['required','image'],
            'cate' => ['required'],
            'post_date' => ['required','after_or_equal:today'],
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
            'img_blog.required' => 'Vui lòng chọn hình ảnh!',
            'img_blog.image' => 'File không được hỗ trợ!',
            'cate.required' => 'Vui lòng chọn danh mục bài viết!',
            'post_date.required' => 'Vui lòng chọn ngày đăng bài viết!',
            'post_date.after_or_equal'=> 'Vui lòng chọn ngày đăng từ ngày hôm nay trở đi!'
        ];
    }
}