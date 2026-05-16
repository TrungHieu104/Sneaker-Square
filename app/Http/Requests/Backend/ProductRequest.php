<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
            'pro_name' => ['required','max: 255', Rule::unique('products', 'pro_name')->ignore(request()->id, 'pro_id')],
            'pro_slug' => ['required','max: 255', Rule::unique('products', 'pro_slug')->ignore(request()->id, 'pro_id')],
            'pro_code' => ['required', 'max: 50', Rule::unique('products', 'pro_code')->ignore(request()->id, 'pro_id')],
            'capital_price' => ['required', 'numeric', 'min: 1', 'max: 9999999999', 'integer'],
            'pro_price' => ['required', 'numeric', 'min: 1', 'max: 9999999999', 'integer', 'gt:capital_price'],
            'pro_price_sale' => ['nullable', 'numeric', 'min: 0', 'max: 9999999999', 'integer', 'lt:pro_price'],
            'pro_img' => ['required', 'image'],
            'pro_SEO_title' => ['nullable', 'max: 255'],
            'pro_meta_keywords' => ['nullable', 'max: 2000'],
            'pro_meta_description' => ['nullable', 'max: 2000'],
            'pro_date' => ['required'],
            'cate_id' => ['required'],
        ];
    }

    public function messages() 
    {
        return [
            'pro_name.required' => 'Vui lòng nhập tên sản phẩm!',
            'pro_name.max' => 'Tên sản phẩm quá dài!',
            'pro_name.unique' => 'Tên sản phẩm đã tồn tại!',

            'pro_slug.required' => 'Vui lòng nhập tên sản phẩm!',
            'pro_slug.max' => 'Tên sản phẩm quá dài!',
            'pro_slug.unique' => 'Tên sản phẩm đã tồn tại!',

            'pro_code.required' => 'Vui lòng nhập mã sản phẩm!',
            'pro_code.max' => 'Mã sản phẩm quá dài!',
            'pro_code.unique' => 'Mã sản phẩm đã tồn tại!',

            'capital_price.required' => 'Vui lòng nhập giá vốn sản phẩm!',
            'capital_price.numeric' => 'Giá vốn không được nhập chữ!',
            'capital_price.min' => 'Giá vốn phải lớn hơn 0!',
            'capital_price.max' => 'Giá vốn không dài hơn 10 kí tự!',
            'capital_price.integer' => 'Giá vốn phải là số nguyên!',

            'pro_price.required' => 'Vui lòng nhập giá bán sản phẩm!',
            'pro_price.numeric' => 'Giá bán không được nhập chữ!',
            'pro_price.min' => 'Giá bán phải lớn hơn 0!',
            'pro_price.max' => 'Giá bán không dài hơn 10 kí tự!',
            'pro_price.integer' => 'Giá bán phải là số nguyên!',
            'pro_price.gt' => 'Giá bán phải lớn hơn giá vốn!',

            'pro_price_sale.numeric' => 'Giá giảm không được nhập chữ!',
            'pro_price_sale.min' => 'Giá giảm không được là số âm!',
            'pro_price_sale.max' => 'Giá giảm bán không dài hơn 10 kí tự!',
            'pro_price_sale.integer' => 'Giá giảm phải là số nguyên!',
            'pro_price_sale.lt' => 'Giá giảm phải thấp hơn giá bán!',

            'pro_img.required' => 'Vui lòng thêm hình ảnh cho sản phẩm!',
            'pro_img.image' => 'File không được hỗ trợ!',

            'pro_SEO_title.max' => 'SEO title quá dài!',

            'pro_meta_keywords.max' => 'Từ khóa quá dài!',

            'pro_meta_description.max' => 'Mô tả quá dài!',

            'pro_date.required' => 'Vui lòng nhập ngày bán sản phẩm!',

            'cate_id' => 'Vui lòng chọn danh mục sản phẩm!',
        ];
    }
}
