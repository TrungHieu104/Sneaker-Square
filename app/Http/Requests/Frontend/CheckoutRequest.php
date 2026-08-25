<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * The only two things checkout still accepts from the browser.
 *
 * Everything else the form used to post — the order code, the shipping fee,
 * the discount, the total — is worked out on the server now, so there is
 * nothing left here to tamper with. The payment method still has to come from
 * the customer, and it has to be one we recognise.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment' => ['required', 'string', 'in:cod,redirect,payUrl'],
            'note_customer' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment.in' => 'Phương thức thanh toán không hợp lệ.',
            'note_customer.max' => 'Ghi chú không được dài quá 1000 ký tự.',
        ];
    }
}
