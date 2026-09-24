<?php

namespace App\Services\Wallet;

use App\Models\UserModel;
use App\Models\WalletTransactionModel;
use App\Models\WalletWithdrawalModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Money leaving a wallet for a bank account.
 *
 * The balance goes the moment the request is made rather than when the shop
 * makes the transfer. Holding it any later would let the same money be spent
 * on an order while the transfer was being prepared, and the shop would pay
 * twice for it.
 */
class WalletWithdrawals
{
    public function __construct(private WalletService $wallets) {}

    /**
     * @param  array{amount: int, bank_name: string, bank_account: string, account_holder: string}  $data
     *
     * @throws InsufficientBalance
     */
    public function request(UserModel $user, array $data): WalletWithdrawalModel
    {
        return DB::transaction(function () use ($user, $data) {
            $wallet = $this->wallets->for($user);

            $withdrawal = WalletWithdrawalModel::create([
                'wallet_id' => $wallet->wallet_id,
                'amount' => $data['amount'],
                'bank_name' => $data['bank_name'],
                'bank_account' => $data['bank_account'],
                'account_holder' => $data['account_holder'],
                'status' => WalletWithdrawalModel::REQUESTED,
            ]);

            $this->wallets->debit(
                $wallet,
                (int) $withdrawal->amount,
                WalletTransactionModel::TYPE_WITHDRAW,
                'Yêu cầu rút về '.$data['bank_name'].' '.$data['bank_account'],
                WalletService::REF_WITHDRAWAL,
                (int) $withdrawal->withdrawal_id,
            );

            return $withdrawal;
        });
    }

    /**
     * @return bool whether this call is the one that closed the request
     */
    public function markPaid(WalletWithdrawalModel $withdrawal, ?string $note = null): bool
    {
        return $this->decide($withdrawal, function (WalletWithdrawalModel $fresh) use ($note) {
            $fresh->status = WalletWithdrawalModel::PAID;
            $fresh->note = $note;
        });
    }

    /**
     * @return bool whether this call is the one that closed the request
     */
    public function reject(WalletWithdrawalModel $withdrawal, string $reason): bool
    {
        return $this->decide($withdrawal, function (WalletWithdrawalModel $fresh) use ($reason) {
            $fresh->status = WalletWithdrawalModel::REJECTED;
            $fresh->note = $reason;

            $this->wallets->credit(
                $fresh->wallet,
                (int) $fresh->amount,
                WalletTransactionModel::TYPE_WITHDRAW_REVERSED,
                'Yêu cầu rút bị từ chối: '.$reason,
                WalletService::REF_WITHDRAWAL,
                (int) $fresh->withdrawal_id,
            );
        });
    }

    /**
     * @param  callable(WalletWithdrawalModel): void  $change
     */
    private function decide(WalletWithdrawalModel $withdrawal, callable $change): bool
    {
        return (bool) DB::transaction(function () use ($withdrawal, $change) {
            $fresh = WalletWithdrawalModel::where('withdrawal_id', $withdrawal->withdrawal_id)
                ->lockForUpdate()
                ->first();

            if (! $fresh || $fresh->status !== WalletWithdrawalModel::REQUESTED) {
                return false;
            }

            $change($fresh);
            $fresh->decided_at = Carbon::now();
            $fresh->save();

            return true;
        });
    }
}
