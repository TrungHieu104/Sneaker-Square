<?php

namespace App\Http\Requests\Frontend;

use App\Models\WalletTopupModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class WalletTopupRequest extends FormRequest
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
            'amount' => ['required', 'integer', 'min:'.WalletTopupModel::MIN_AMOUNT, 'max:'.WalletTopupModel::MAX_AMOUNT],
            'gateway' => ['required', 'string', 'in:payUrl,redirect'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Vui lòng nhập số tiền muốn nạp.',
            'amount.integer' => 'Số tiền phải là số nguyên.',
            'amount.min' => 'Nạp tối thiểu :min đ.',
            'amount.max' => 'Nạp tối đa :max đ một lần.',
            'gateway.required' => 'Vui lòng chọn cổng thanh toán.',
            'gateway.in' => 'Cổng thanh toán không hợp lệ.',
        ];
    }
}
