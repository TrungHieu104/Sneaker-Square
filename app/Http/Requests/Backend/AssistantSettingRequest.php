<?php

namespace App\Http\Requests\Backend;

use App\Services\ShopSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssistantSettingRequest extends FormRequest
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
            'assistant_model' => ['required', Rule::in(array_keys(ShopSettings::ASSISTANT_MODELS))],
        ];
    }

    /**
     * Back to the assistant tab, or the error would sit on a tab that is not open.
     */
    protected function getRedirectUrl(): string
    {
        return route('setting.edit').'#tab-tro-ly';
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assistant_model.required' => 'Vui lòng chọn model.',
            'assistant_model.in' => 'Model này không có trong danh sách.',
        ];
    }
}
