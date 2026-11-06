<?php

namespace App\Http\Requests\Backend;

use App\Enums\CustomerSegment;
use App\Services\ShopSettings;
use Illuminate\Foundation\Http\FormRequest;

class SegmentOfferSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * An unticked switch sends nothing, which has to mean "no ceiling".
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['cashback_enabled' => ['nullable', 'boolean']];

        foreach (CustomerSegment::cases() as $segment) {
            $field = 'offers.'.$segment->value.'.';

            foreach (['discount', 'cashback'] as $name) {
                [$min, $max] = ShopSettings::SEGMENT_OFFER_LIMITS[$name];
                $rules[$field.$name] = ['required', 'integer', 'min:'.$min, 'max:'.$max];
            }

            [$min, $max] = ShopSettings::SEGMENT_OFFER_LIMITS['cap'];
            $rules[$field.'cap_enabled'] = ['nullable', 'boolean'];
            $rules[$field.'cap'] = ['nullable', 'required_if_accepted:'.$field.'cap_enabled', 'integer', 'min:'.$min, 'max:'.$max];
        }

        return $rules;
    }

    /**
     * @return array<string, array{discount: int, cashback: int, cap: ?int}>
     */
    public function offers(): array
    {
        $offers = [];

        foreach (CustomerSegment::cases() as $segment) {
            $input = (array) $this->validated('offers.'.$segment->value);

            $offers[$segment->value] = [
                'discount' => (int) $input['discount'],
                'cashback' => (int) $input['cashback'],
                'cap' => ! empty($input['cap_enabled']) ? (int) $input['cap'] : null,
            ];
        }

        return $offers;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required' => 'Vui lòng nhập giá trị.',
            '*.required_if_accepted' => 'Vui lòng nhập mức tối đa hoặc tắt giới hạn.',
            '*.integer' => 'Giá trị phải là số nguyên.',
            '*.min' => 'Tối thiểu là :min.',
            '*.max' => 'Tối đa là :max.',
        ];
    }
}
