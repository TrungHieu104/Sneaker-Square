<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Http\Requests\Frontend\WalletTopupRequest;
use App\Http\Requests\Frontend\WalletWithdrawRequest;
use App\Services\Payment\InvalidPaymentCallbackException;
use App\Services\Wallet\InsufficientBalance;
use App\Services\Wallet\WalletService;
use App\Services\Wallet\WalletTopups;
use App\Services\Wallet\WalletWithdrawals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * The customer's own view of their wallet: balance, statement, top up, cash out.
 */
class WalletController extends Controller
{
    use SharesStorefrontLayout;

    public function __construct(
        private readonly WalletService $wallets,
        private readonly WalletTopups $topups,
        private readonly WalletWithdrawals $withdrawals,
    ) {
        $this->shareStorefrontLayout();
    }

    public function index()
    {
        $wallet = $this->wallets->for(Auth::user());

        return view('frontend.pages.account.user_info.pages.wallet', [
            'wallet' => $wallet,
            'transactions' => $wallet->transactions()->paginate(15),
            'withdrawals' => $wallet->withdrawals()->limit(10)->get(),
        ]);
    }

    public function topup(WalletTopupRequest $request): RedirectResponse
    {
        try {
            $topup = $this->topups->start(
                Auth::user(),
                (int) $request->input('amount'),
                (string) $request->input('gateway'),
            );
        } catch (InvalidPaymentCallbackException $e) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', 'Không kết nối được cổng thanh toán. Vui lòng thử lại.');
        }

        return redirect()->away($topup->checkout_url);
    }

    public function withdraw(WalletWithdrawRequest $request): RedirectResponse
    {
        try {
            $this->withdrawals->request(Auth::user(), [
                'amount' => (int) $request->input('amount'),
                'bank_name' => (string) $request->input('bank_name'),
                'bank_account' => (string) $request->input('bank_account'),
                'account_holder' => (string) $request->input('account_holder'),
            ]);
        } catch (InsufficientBalance $e) {
            Session::flash('iconMessage', 'error');

            return back()->with('message', $e->getMessage());
        }

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Đã gửi yêu cầu rút tiền. Cửa hàng xử lý trong 1-3 ngày làm việc.');
    }
}
