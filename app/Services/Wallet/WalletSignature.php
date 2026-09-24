<?php

namespace App\Services\Wallet;

use App\Models\WalletModel;
use App\Models\WalletTransactionModel;

/**
 * Signs what the database cannot be trusted to hold on its own.
 *
 * Nothing here stops somebody with a MySQL account from writing a different
 * number — nothing in PHP can. What it does is make the change show: the key
 * lives in the environment, not in the database, so a row edited by hand
 * carries a signature that no longer belongs to it.
 *
 * Two separate chains, because they protect two different tables:
 *
 *   balance  — signed over the wallet's own row, including a version that goes
 *              up on every movement, so yesterday's valid pair cannot be put
 *              back to restore a spent balance.
 *   ledger   — each row signed over the row before it, so removing or editing
 *              one row breaks every row after it.
 */
class WalletSignature
{
    /**
     * @param  ?string  $lastEntryHash  the wallet's newest ledger hash, which
     *                                  is looked up when not supplied
     */
    public function balanceHash(WalletModel $wallet, ?string $lastEntryHash = null): string
    {
        return $this->sign([
            'wallet',
            $wallet->user_id,
            $wallet->balance,
            $wallet->version,
            // Ties the balance to the ledger it came from. Without this, putting
            // yesterday's balance, version and signature back together would
            // verify: all three are the attacker's to copy. They cannot copy
            // this one, because it moves every time money does.
            $lastEntryHash ?? $this->lastEntryHash((int) $wallet->wallet_id) ?? '',
        ]);
    }

    public function balanceMatches(WalletModel $wallet): bool
    {
        // A wallet written before signing existed has nothing to compare
        // against; the reconciliation command reports those separately rather
        // than locking their owner out of the shop.
        if ($wallet->balance_hash === null) {
            return true;
        }

        return hash_equals($this->balanceHash($wallet), (string) $wallet->balance_hash);
    }

    public function entryHash(WalletTransactionModel $entry, ?string $previousHash): string
    {
        return $this->sign([
            'entry',
            $previousHash ?? '',
            $entry->wallet_id,
            $entry->type,
            $entry->direction,
            $entry->amount,
            $entry->balance_after,
            $entry->reference_type ?? '',
            $entry->reference_id ?? '',
            $entry->created_at?->format('Y-m-d H:i:s') ?? '',
        ]);
    }

    public function entryMatches(WalletTransactionModel $entry, ?string $previousHash): bool
    {
        if ($entry->entry_hash === null) {
            return true;
        }

        return hash_equals($this->entryHash($entry, $previousHash), (string) $entry->entry_hash);
    }

    /**
     * The newest ledger hash of a wallet, which the next row chains onto.
     */
    public function lastEntryHash(int $walletId): ?string
    {
        return WalletTransactionModel::where('wallet_id', $walletId)
            ->orderByDesc('transaction_id')
            ->value('entry_hash');
    }

    /**
     * @param  array<int, mixed>  $parts
     */
    private function sign(array $parts): string
    {
        return hash_hmac('sha256', implode('|', $parts), $this->key());
    }

    private function key(): string
    {
        return (string) (config('services.wallet.signing_key') ?: config('app.key'));
    }
}
