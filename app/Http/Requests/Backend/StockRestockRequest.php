<?php

namespace App\Http\Requests\Backend;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;

/**
 * Rules for adding stock to one variant already on the shelf.
 *
 * Prices are left out on purpose: the row has its own form for those, and a
 * delivery that quietly repriced the variant is how the intake form loses a
 * per-size price.
 */
class StockRestockRequest extends FormRequest
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
            'quantity' => ['required', 'numeric', 'integer', 'gt:0', 'max:999999'],
            'quantity_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Vui lòng nhập số lượng!',
            'quantity.gt' => 'Vui lòng nhập số lượng lớn hơn 0!',
            'quantity.integer' => 'Số lượng phải là số nguyên!',
            'quantity_date.required' => 'Vui lòng nhập ngày nhập hàng!',
            'quantity_date.date' => 'Ngày nhập hàng không hợp lệ!',
        ];
    }

    /**
     * One of these forms per row, so the errors go into a bag named after the
     * variant rather than reddening every modal on the page.
     */
    protected function prepareForValidation(): void
    {
        $this->errorBag = self::errorBagFor($this->route('quantity_id'));
    }

    public static function errorBagFor($quantityId): string
    {
        return 'stock-restock-'.$quantityId;
    }

    protected function failedValidation(Validator $validator): void
    {
        Session::flash('iconMessage', 'error');
        Session::flash('message', 'Nhập thêm hàng thất bại!');

        parent::failedValidation($validator);
    }
}
