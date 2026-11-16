<?php

namespace App\Http\Requests\Backend;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:5', 'max:255'],
            'group_id' => ['required', 'integer', 'exists:faq_groups,group_id'],
            'answer' => ['required', 'string', 'min:3'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => ['required', 'in:0,1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Vui lòng nhập câu hỏi!',
            'question.min' => 'Câu hỏi quá ngắn!',
            'question.max' => 'Câu hỏi dài tối đa 255 ký tự!',
            'group_id.required' => 'Vui lòng chọn nhóm!',
            'group_id.exists' => 'Nhóm không tồn tại!',
            'answer.required' => 'Vui lòng nhập câu trả lời!',
            'answer.min' => 'Câu trả lời quá ngắn!',
            'position.integer' => 'Thứ tự phải là số!',
            'position.min' => 'Thứ tự không được âm!',
            'position.max' => 'Thứ tự tối đa là 999!',
            'status.in' => 'Trạng thái hiển thị không hợp lệ!',
        ];
    }
}
