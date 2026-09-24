<?php

namespace App\Http\Requests\Frontend;

use App\Models\OrderReturnModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnOrderRequest extends FormRequest
{
    public const MAX_IMAGES = 3;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(array_keys(OrderReturnModel::REASONS))],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'required_if:reason,khac', 'string', 'max:1000'],
            'refund_info' => ['required', 'string', 'max:500'],
            'images' => ['nullable', 'array', 'max:'.self::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng chọn lý do trả hàng.',
            'items.required' => 'Vui lòng chọn sản phẩm muốn trả.',
            'items.*.integer' => 'Số lượng trả phải là số nguyên.',
            'items.*.min' => 'Số lượng trả không được âm.',
            'reason.in' => 'Lý do trả hàng không hợp lệ.',
            'description.required_if' => 'Vui lòng mô tả lý do trả hàng.',
            'description.max' => 'Mô tả tối đa :max ký tự.',
            'refund_info.required' => 'Vui lòng nhập thông tin nhận tiền hoàn.',
            'refund_info.max' => 'Thông tin nhận tiền hoàn tối đa :max ký tự.',
            'images.max' => 'Chỉ gửi tối đa :max ảnh.',
            'images.*.image' => 'Tệp đính kèm phải là ảnh.',
            'images.*.mimes' => 'Ảnh phải có định dạng jpg, png hoặc webp.',
            'images.*.max' => 'Mỗi ảnh tối đa 5MB.',
        ];
    }
}
