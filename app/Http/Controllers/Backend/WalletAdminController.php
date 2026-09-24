<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use App\Models\WalletWithdrawalModel;
use App\Services\Wallet\WalletWithdrawals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * The shop's side of the wallets: what it owes, and the transfers it has to make.
 */
class WalletAdminController extends Controller
{
    /**
     * The admin navbar prints the search box's last keyword on every page.
     */
    public function __construct(Request $request, private WalletWithdrawals $withdrawals)
    {
        ViewFacade::share('keyword', $request->input('keyword'));
    }

    public function index(Request $request)
    {
        $status = (string) $request->input('status', WalletWithdrawalModel::REQUESTED);

        if (! in_array($status, [WalletWithdrawalModel::REQUESTED, WalletWithdrawalModel::PAID, WalletWithdrawalModel::REJECTED], true)) {
            $status = WalletWithdrawalModel::REQUESTED;
        }

        return view('backend.pages.wallet.withdrawal_list', [
            'status' => $status,
            'requests' => WalletWithdrawalModel::with('wallet.user')
                ->where('status', $status)
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString(),
            'counts' => [
                WalletWithdrawalModel::REQUESTED => WalletWithdrawalModel::where('status', WalletWithdrawalModel::REQUESTED)->count(),
                WalletWithdrawalModel::PAID => WalletWithdrawalModel::where('status', WalletWithdrawalModel::PAID)->count(),
                WalletWithdrawalModel::REJECTED => WalletWithdrawalModel::where('status', WalletWithdrawalModel::REJECTED)->count(),
            ],
            // What the shop is holding for its customers, which is a liability
            // and not the same thing as the money in its own account.
            'held' => (int) WalletModel::sum('balance'),
            'pending' => (int) WalletWithdrawalModel::where('status', WalletWithdrawalModel::REQUESTED)->sum('amount'),
            'toppedUp' => (int) WalletTransactionModel::where('type', WalletTransactionModel::TYPE_TOPUP)->sum('amount'),
            'refunded' => (int) WalletTransactionModel::where('type', WalletTransactionModel::TYPE_REFUND)->sum('amount'),
        ]);
    }

    public function markPaid(Request $request, string $withdrawal_id): RedirectResponse
    {
        $data = $request->validate(
            ['note' => ['nullable', 'string', 'max:255']],
            ['note.max' => 'Ghi chú tối đa :max ký tự.'],
        );

        $done = $this->withdrawals->markPaid(
            WalletWithdrawalModel::findOrFail($withdrawal_id),
            $data['note'] ?? null,
        );

        return $this->back($done, 'Đã đánh dấu chuyển khoản xong.');
    }

    public function reject(Request $request, string $withdrawal_id): RedirectResponse
    {
        $data = $request->validate(
            ['reason' => ['required', 'string', 'max:255']],
            ['reason.required' => 'Vui lòng nhập lý do từ chối.', 'reason.max' => 'Lý do tối đa :max ký tự.'],
        );

        $done = $this->withdrawals->reject(
            WalletWithdrawalModel::findOrFail($withdrawal_id),
            $data['reason'],
        );

        return $this->back($done, 'Đã từ chối và trả tiền lại vào ví khách.');
    }

    private function back(bool $done, string $message): RedirectResponse
    {
        Session::flash('iconMessage', $done ? 'success' : 'error');

        return back()->with(
            'message',
            $done ? $message : 'Yêu cầu này đã được xử lý trước đó.',
        );
    }
}
