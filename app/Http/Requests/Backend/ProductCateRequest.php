<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules for creating or editing a product category.
 *
 * See ColorRequest: the unique rules ignored a route parameter that does not
 * exist, so an edit always collided with the row being edited.
 */
class ProductCateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Capitalises the category name before it is checked, matching what the
     * controller stores.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('cate_name')) {
            $this->merge(['cate_name' => ucwords((string) $this->input('cate_name'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cate_name' => ['required', Rule::unique('category', 'cate_name')->ignore($this->route('product_category'), 'cate_id')],
            'cate_sort' => ['required', 'numeric', 'min: 0', 'max: 9999999999', 'integer', Rule::unique('category', 'cate_sort')->ignore($this->route('product_category'), 'cate_id')],
            'cate_img' => ['nullable', 'image'],
        ];
    }

    public function messages(){
        return [
            'cate_name.required' => 'Vui lòng nhập tên danh mục!',
            'cate_name.unique' => 'Tên danh mục đã tồn tại!',

            'cate_sort.required' => 'Vui lòng nhập số thứ tự!',
            'cate_sort.max' => 'Số thứ tự không dài hơn 10 kí tự!',
            'cate_sort.numeric' => 'Số thứ tự không được nhập chữ!',
            'cate_sort.min' => 'Số thứ tự không được là số âm!',
            'cate_sort.integer' => 'Số thứ tự phải là số nguyên!',
            'cate_sort.unique' => 'Số thứ tự đã tồn tại!',

            'cate_img.image' => 'File không được hỗ trợ!',
        ];
    }
}
