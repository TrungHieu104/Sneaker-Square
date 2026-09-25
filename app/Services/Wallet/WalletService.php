<?php

namespace App\Services\Wallet;

use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Models\UserModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The only way money moves in or out of a customer's wallet.
 *
 * Every movement writes two things under the wallet's row lock: the ledger
 * entry and the new balance. Nothing else may touch `wallets.balance` — a
 * second writer would eventually hand a customer a balance the ledger cannot
 * account for, and a wallet nobody can reconcile is a wallet nobody can
 * refund from.
 *
 * Both writes are signed with a key held outside the database, so "nothing
 * else may touch it" stops being a rule the code merely hopes for: a row
 * changed by anything but this class no longer verifies. See WalletSignature
 * and the `wallet:doi-soat` command.
 */
class WalletService
{
    public function __construct(private WalletSignature $signature) {}

    public const REF_ORDER = 'order';

    public const REF_ORDER_REFUND = 'order_refund';

    /**
     * Keyed on the return, not the order: an order may be sent back in more
     * than one batch, and each batch is paid for once.
     */
    public const REF_RETURN_REFUND = 'return_refund';

    public const REF_TOPUP = 'topup';

    public const REF_WITHDRAWAL = 'withdrawal';

    /**
     * The customer's wallet, created the first time it is looked at.
     *
     * @throws WalletTampered when the row does not match its own signature
     */
    public function for(UserModel|int $user): WalletModel
    {
        $userId = $user instanceof UserModel ? (int) $user->user_id : $user;
        // `version` is spelled out rather than left to the column default:
        // firstOrCreate does not read defaults back, and signing a wallet whose
        // version is still null produces a hash the next read cannot reproduce.
        $wallet = WalletModel::firstOrCreate(['user_id' => $userId], ['balance' => 0, 'version' => 0]);

        if ($wallet->balance_hash === null) {
            $wallet->balance_hash = $this->signature->balanceHash($wallet);
            $wallet->save();
        }

        return $this->checked($wallet);
    }

    /**
     * Refuses to hand back a wallet whose balance was written by something
     * other than this service. Spending, refunding or even showing a number
     * nobody can vouch for is worse than the page failing.
     *
     * @throws WalletTampered
     */
    public function checked(WalletModel $wallet): WalletModel
    {
        if (! $this->signature->balanceMatches($wallet)) {
            // Which wallet and what it now says is for whoever investigates.
            // The customer gets told their wallet is held and nothing else:
            // naming the mechanism only helps somebody probing it.
            Log::critical('Wallet balance does not match its signature', [
                'wallet_id' => $wallet->wallet_id,
                'user_id' => $wallet->user_id,
                'balance' => $wallet->balance,
                'version' => $wallet->version,
            ]);

            throw new WalletTampered;
        }

        return $wallet;
    }

    public function credit(
        WalletModel $wallet,
        int $amount,
        string $type,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): WalletTransactionModel {
        return $this->move($wallet, $amount, WalletTransactionModel::IN, $type, $description, $referenceType, $referenceId);
    }

    /**
     * @throws InsufficientBalance when the wallet cannot cover the amount
     */
    public function debit(
        WalletModel $wallet,
        int $amount,
        string $type,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): WalletTransactionModel {
        return $this->move($wallet, $amount, WalletTransactionModel::OUT, $type, $description, $referenceType, $referenceId);
    }

    /**
     * Sends money back for an order the shop is not going to deliver.
     *
     * Safe to call twice: the reference is the order, so a cancellation that
     * runs again — or a refund confirmed by two admins at once — finds the
     * entry already written and pays nothing more.
     *
     * @return bool whether this call is the one that paid the money back
     */
    public function refundOrder(OrderModel $order, int $amount, string $description): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return (bool) DB::transaction(function () use ($order, $amount, $description) {
            $wallet = $this->for((int) $order->user_id);

            if ($this->alreadyRecorded($wallet, self::REF_ORDER_REFUND, (int) $order->order_id)) {
                return false;
            }

            $this->credit(
                $wallet,
                $amount,
                WalletTransactionModel::TYPE_REFUND,
                $description,
                self::REF_ORDER_REFUND,
                (int) $order->order_id,
            );

            return true;
        });
    }

    /**
     * Pays back one accepted return.
     *
     * @return bool whether this call is the one that paid the money back
     */
    public function refundReturn(OrderReturnModel $return, int $amount, string $description): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return (bool) DB::transaction(function () use ($return, $amount, $description) {
            $wallet = $this->for((int) $return->order->user_id);

            if ($this->alreadyRecorded($wallet, self::REF_RETURN_REFUND, (int) $return->return_id)) {
                return false;
            }

            $this->credit(
                $wallet,
                $amount,
                WalletTransactionModel::TYPE_REFUND,
                $description,
                self::REF_RETURN_REFUND,
                (int) $return->return_id,
            );

            return true;
        });
    }

    public function alreadyRecorded(WalletModel $wallet, string $referenceType, int $referenceId): bool
    {
        return WalletTransactionModel::where('wallet_id', $wallet->wallet_id)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->exists();
    }

    private function move(
        WalletModel $wallet,
        int $amount,
        string $direction,
        string $type,
        ?string $description,
        ?string $referenceType,
        ?int $referenceId,
    ): WalletTransactionModel {
        if ($amount <= 0) {
            throw new InsufficientBalance('Số tiền giao dịch phải lớn hơn 0.');
        }

        return DB::transaction(function () use ($wallet, $amount, $direction, $type, $description, $referenceType, $referenceId) {
            // Re-read under the lock: the instance handed in may have been
            // loaded before another request moved the same money.
            $locked = WalletModel::where('wallet_id', $wallet->wallet_id)->lockForUpdate()->firstOrFail();

            if ($direction === WalletTransactionModel::OUT && ! $locked->canAfford($amount)) {
                throw new InsufficientBalance(
                    'Số dư ví không đủ: cần '.number_format($amount, 0, ',', '.')
                    .' đ, hiện có '.number_format($locked->balance, 0, ',', '.').' đ.'
                );
            }

            $this->checked($locked);

            $balance = $locked->balance + ($direction === WalletTransactionModel::IN ? $amount : -$amount);

            $entry = new WalletTransactionModel([
                'wallet_id' => $locked->wallet_id,
                'type' => $type,
                'direction' => $direction,
                'amount' => $amount,
                'balance_after' => $balance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_at' => Carbon::now(),
            ]);

            // The ledger row is signed first, over the row before it, because
            // the balance is then signed over this one — the two chains meet
            // here so neither can be rolled back without the other.
            $entry->entry_hash = $this->signature->entryHash(
                $entry,
                $this->signature->lastEntryHash((int) $locked->wallet_id),
            );
            $entry->save();

            $locked->balance = $balance;
            $locked->version++;
            $locked->balance_hash = $this->signature->balanceHash($locked, $entry->entry_hash);
            $locked->save();

            $wallet->balance = $locked->balance;
            $wallet->version = $locked->version;
            $wallet->balance_hash = $locked->balance_hash;

            return $entry;
        });
    }
}
