<?php

namespace App\Http\Requests\Backend;

use App\Services\ShopSettings;
use Illuminate\Foundation\Http\FormRequest;

class ShopSettingRequest extends FormRequest
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
        $days = ['required', 'integer', 'min:'.ShopSettings::MIN_DAYS, 'max:'.ShopSettings::MAX_DAYS];

        return [
            'auto_complete_days' => $days,
            'return_days' => $days,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required' => 'Vui lòng nhập số ngày.',
            '*.integer' => 'Số ngày phải là số nguyên.',
            '*.min' => 'Số ngày tối thiểu là :min.',
            '*.max' => 'Số ngày tối đa là :max.',
        ];
    }
}
