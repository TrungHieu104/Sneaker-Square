<?php

namespace App\Http\Requests\Backend;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

/**
 * The carrier's own code for a parcel, typed in by the shop.
 *
 * It has to be unique: it is the key the status callbacks are matched on, and
 * two orders claiming the same parcel would send one order's history to the
 * other's customer.
 */
class ShippingCodeRequest extends FormRequest
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
            'order_shipping_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('order', 'order_shipping_code')->ignore($this->route('order_id'), 'order_id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'order_shipping_code.max' => 'Mã vận đơn quá dài!',
            'order_shipping_code.unique' => 'Mã vận đơn này đã gắn cho đơn hàng khác!',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Session::flash('iconMessage', 'error');
        Session::flash('message', 'Lưu mã vận đơn thất bại!');

        parent::failedValidation($validator);
    }
}
