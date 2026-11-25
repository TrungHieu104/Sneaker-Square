<?php

namespace App\Http\Requests\Backend;

use App\Models\AssistantKnowledgeModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssistantAnswerRequest extends FormRequest
{
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
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:2000'],
            'scope' => ['required', Rule::in([
                AssistantKnowledgeModel::SCOPE_ALL,
                AssistantKnowledgeModel::SCOPE_CATEGORY,
                AssistantKnowledgeModel::SCOPE_PRODUCT,
            ])],
            'scope_id' => [
                'nullable',
                'required_unless:scope,'.AssistantKnowledgeModel::SCOPE_ALL,
                'integer',
                Rule::when($this->input('scope') === AssistantKnowledgeModel::SCOPE_PRODUCT, [Rule::exists('products', 'pro_id')]),
                Rule::when($this->input('scope') === AssistantKnowledgeModel::SCOPE_CATEGORY, [Rule::exists('category', 'cate_id')]),
            ],
            // Saved from a chat: the staff reply it comes from, which tells which question it answers.
            'message_id' => [Rule::requiredIf(fn () => $this->routeIs('assistant_admin.save_answer')), 'nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Vui lòng nhập câu hỏi.',
            'answer.required' => 'Vui lòng nhập câu trả lời.',
            'answer.max' => 'Câu trả lời tối đa :max ký tự.',
            'scope.in' => 'Phạm vi áp dụng không hợp lệ.',
            'scope_id.required_unless' => 'Câu hỏi này không gắn với sản phẩm nào, hãy chọn Toàn shop.',
            'scope_id.exists' => 'Sản phẩm hoặc danh mục này không còn, hãy chọn phạm vi khác.',
        ];
    }
}
