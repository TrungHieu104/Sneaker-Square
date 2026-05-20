<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class PromotionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return  true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required','min:3', 'max:100','unique:promotion,promotion_name'],
            'start_pro' => ['required','after_or_equal:today'],
            'end_pro' => ['required','after:start_start', 'after_or_equal:today'],
            'position' => ['required','min:0', 'max:100000000', 'numeric','integer','unique:promotion,promotion_sort'],
            'img' => ['required'],
            'cate' => ['required'],
            'url' => ['required','max:200'],
            'type' => ['required'],
            'contents' => ['required','min:3', 'max:200'],
        ];
    } 
    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập tiêu đề Slide!',
            'name.min' => 'Tiêu đề quá ngắn',
            'name.max' => 'Tiều đề quá dài',
            'name.unique' => 'Tiều đề đã tồn tại!',
            'contents.required' => 'Vui lòng nhập nội dung!',
            'contents.min' => 'Nội dung quá ngắn!',
            'contents.max' => 'Nội dung quá dài!',
            'start_pro.required' => 'Vui lòng chọn ngày bắt đầu!',
            'start_pro.after_or_equal' => 'Ngày bắt đầu phải bằng hoặc lớn hơn ngày hiện tại!',
            'end_pro.required' => 'Vui lòng chọn ngày kết thúc!',
            'end_pro.after' => 'Ngày kết thúc phải sau ngày bắt đầu!',
            'end_pro.after_or_equal' => 'Ngày kết thúc phải sau ngày bắt đầu và ngày hiện tại!',
            'position.required' => 'Vui lòng nhập số lượng!',
            'position.min' => 'Vui lòng nhập số lượng lớn hơn 0!',
            'position.max' => 'Số lượng mã quá lớn!',
            'position.numeric' => 'Số lượng mã không được nhập chữ!',
            'position.integer' => 'Vui lòng nhập số nguyên!',
            'position.unique' => 'Số thứ tự này đã tồn tại!',
            'img.required' => 'Vui lòng chọn hình ảnh!',
            'cate.required' => 'Vui lòng tạo danh mục Slide!',
            'url.required' => 'Vui lòng nhập đường dẫn!',
            'url.max' => 'Đường dẫn quá dài!',
            'type.required' => 'Vui lòng nhập thể loại!',
        ];
    }
}
