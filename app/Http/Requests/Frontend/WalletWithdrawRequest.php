<?php

namespace App\Http\Requests\Frontend;

use App\Models\WalletWithdrawalModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class WalletWithdrawRequest extends FormRequest
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
            'amount' => ['required', 'integer', 'min:'.WalletWithdrawalModel::MIN_AMOUNT],
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            'account_holder' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Vui lòng nhập số tiền muốn rút.',
            'amount.integer' => 'Số tiền phải là số nguyên.',
            'amount.min' => 'Rút tối thiểu :min đ.',
            'bank_name.required' => 'Vui lòng nhập tên ngân hàng.',
            'bank_account.required' => 'Vui lòng nhập số tài khoản.',
            'bank_account.regex' => 'Số tài khoản chỉ gồm chữ số.',
            'account_holder.required' => 'Vui lòng nhập tên chủ tài khoản.',
        ];
    }
}
