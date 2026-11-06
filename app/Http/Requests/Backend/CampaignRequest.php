<?php

namespace App\Http\Requests\Backend;

use App\Services\ShopSettings;
use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    private const MAX_DAYS = 30;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        [$minPercent, $maxPercent] = ShopSettings::SEGMENT_OFFER_LIMITS['discount'];

        return [
            'name' => ['required', 'string', 'max:100'],
            'percent' => ['required', 'integer', 'min:'.$minPercent, 'max:'.$maxPercent],
            'min_order' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'max_discount' => ['nullable', 'integer', 'min:1000', 'max:10000000'],
            'days' => ['required', 'integer', 'min:1', 'max:'.self::MAX_DAYS],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
            'recipients' => ['required', 'array'],
            'recipients.*' => ['integer'],
        ];
    }

    /**
     * @return list<int>
     */
    public function recipientIds(): array
    {
        return array_map('intval', (array) $this->validated('recipients'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Before the wildcard: the first matching message wins.
            'recipients.required' => 'Vui lòng chọn ít nhất một khách.',
            '*.required' => 'Vui lòng nhập giá trị.',
            '*.integer' => 'Giá trị phải là số nguyên.',
            '*.max' => 'Giá trị quá lớn hoặc quá dài.',
            'percent.min' => 'Mức giảm tối thiểu :min%.',
            'percent.max' => 'Mức giảm tối đa :max%.',
            'max_discount.min' => 'Giảm tối đa ít nhất 1.000đ.',
            'min_order.min' => 'Đơn tối thiểu không được âm.',
            'days.min' => 'Hiệu lực tối thiểu :min ngày.',
        ];
    }
}
