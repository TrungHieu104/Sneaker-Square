<?php

namespace App\Http\Requests\Backend;

use App\Services\ShopSettings;
use Illuminate\Foundation\Http\FormRequest;

class CartReminderSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * An unticked checkbox sends nothing, which has to mean "off".
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'second_enabled' => $this->boolean('second_enabled'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'enabled' => ['boolean'],
            'second_enabled' => ['boolean'],
            'second_hours' => ['gt:first_hours'],
        ];

        foreach (ShopSettings::CART_REMINDER_LIMITS as $field => [$min, $max]) {
            $rules[$field] = array_merge(['required', 'integer', 'min:'.$min, 'max:'.$max], $rules[$field] ?? []);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required' => 'Vui lòng nhập giá trị.',
            '*.integer' => 'Giá trị phải là số nguyên.',
            '*.min' => 'Tối thiểu là :min.',
            '*.max' => 'Tối đa là :max.',
            'second_hours.gt' => 'Lần nhắc thứ hai phải sau lần nhắc đầu.',
        ];
    }
}
