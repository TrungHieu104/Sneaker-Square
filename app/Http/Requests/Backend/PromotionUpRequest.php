<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class PromotionUpRequest extends FormRequest
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
            'name' => ['required','min:3', 'max:100',Rule::unique('promotion', 'promotion_name')->ignore( request()->id,'promotion_id')],
            'start_pro' => ['required'],
            'end_pro' => ['required','after:start_pro'],
            'position' => ['required','min:0', 'max:100000000', 'numeric','integer',Rule::unique('promotion', 'promotion_sort')->ignore( request()->id,'promotion_id')],
            'url' => ['required','max:200'],
            'type' => ['required'],
            'contents' => ['required','min:3', 'max:200'],
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Vui lòng nhập tiêu đề Slide!',
            'name.min' => 'Tiêu đề quá ngắn!',
            'name.max' => 'Tiều đề quá dài!',
            'name.unique' => 'Tiều đề đã tồn tại!',
            'contents.required' => 'Vui lòng nhập nội dung!',
            'contents.min' => 'Nội dung quá ngắn!',
            'contents.max' => 'Nội dung quá dài!',
            'start_pro.required' => 'Vui lòng chọn ngày bắt đầu!',
            'end_pro.required' => 'Vui lòng chọn ngày kết thúc!',
            'end_pro.after' => 'Ngày kết thúc phải sau ngày bắt đầu!',
            'position.required' => 'Vui lòng nhập thứ tự!',
            'position.min' => 'Vui lòng nhập số thứ tự lớn hơn 0!',
            'position.max' => 'Số thứ tự quá lớn!',
            'position.numeric' => 'Số thứ tự không được nhập chữ!',
            'position.integer' => 'Vui lòng nhập số nguyên!',
            'position.unique' => 'Số thứ tự đã tồn tại!',
            'url.required' => 'Vui lòng nhập đường dẫn!',
            'url.max' => 'Đường dẫn quá dài',
            'type.required' => 'Vui lòng nhập thể loại!',
        ];
    }
}
