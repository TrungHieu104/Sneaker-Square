<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer's balance with the shop, in whole dong.
 *
 * `balance` is a cache of the ledger next door, kept because every page that
 * shows a wallet shows the balance and summing the ledger each time would not
 * scale. Only WalletService may write it, under the row lock, in the same
 * transaction as the matching transaction row — the two must never disagree.
 *
 * `balance_hash` signs the row so that disagreement is visible even when it
 * was introduced outside the application, and `version` goes up on every
 * movement so an old balance cannot be restored together with its old
 * signature. See WalletSignature.
 */
class WalletModel extends Model
{
    protected $table = 'wallets';

    protected $primaryKey = 'wallet_id';

    protected $fillable = ['user_id', 'balance', 'version', 'balance_hash'];

    protected $casts = ['balance' => 'integer', 'version' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransactionModel::class, 'wallet_id', 'wallet_id')->orderByDesc('created_at');
    }

    public function topups(): HasMany
    {
        return $this->hasMany(WalletTopupModel::class, 'wallet_id', 'wallet_id')->orderByDesc('created_at');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(WalletWithdrawalModel::class, 'wallet_id', 'wallet_id')->orderByDesc('created_at');
    }

    public function canAfford(int $amount): bool
    {
        return $this->balance >= $amount;
    }
}
