<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class AssistantMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:500'],
            'pro_id' => ['nullable', 'integer'],
            'sau' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Bạn chưa nhập câu hỏi.',
            'message.max' => 'Câu hỏi dài quá, bạn rút gọn dưới 500 ký tự giúp mình nhé.',
        ];
    }
}
