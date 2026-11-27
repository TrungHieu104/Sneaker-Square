<?php

namespace App\Http\Requests\Backend;

use App\Models\FaqGroupModel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssistantPublishRequest extends FormRequest
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
            'group_id' => [
                'required', 'integer', 'exists:faq_groups,group_id',
                // The assistant reads only the groups the storefront shows.
                function (string $attribute, mixed $value, Closure $fail) {
                    if (FaqGroupModel::whereKey($value)->where('group_hidden', 0)->exists()) {
                        $fail('Nhóm này đang ẩn trên trang câu hỏi thường gặp, trợ lý sẽ không đọc được câu trả lời. Hãy chọn nhóm đang hiện.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'group_id.required' => 'Chọn nhóm câu hỏi để đăng vào.',
            'group_id.exists' => 'Nhóm câu hỏi không tồn tại.',
        ];
    }
}
