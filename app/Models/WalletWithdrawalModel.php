<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A request to move money out of a wallet and into a bank account.
 *
 * The balance leaves the wallet the moment the request is made, not when the
 * shop pays it: otherwise the same money could be spent on an order while the
 * transfer was being prepared. A refusal puts it back.
 */
class WalletWithdrawalModel extends Model
{
    public const REQUESTED = 'requested';

    public const PAID = 'paid';

    public const REJECTED = 'rejected';

    /** @var array<string, string> */
    private const STATUS_LABELS = [
        self::REQUESTED => 'Chờ cửa hàng chuyển',
        self::PAID => 'Đã chuyển',
        self::REJECTED => 'Bị từ chối, đã trả lại ví',
    ];

    public const MIN_AMOUNT = 50000;

    protected $table = 'wallet_withdrawals';

    protected $primaryKey = 'withdrawal_id';

    protected $fillable = [
        'wallet_id',
        'amount',
        'bank_name',
        'bank_account',
        'account_holder',
        'status',
        'note',
        'decided_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'decided_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletModel::class, 'wallet_id', 'wallet_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function badge(): string
    {
        return match ($this->status) {
            self::PAID => 'success',
            self::REJECTED => 'secondary',
            default => 'warning',
        };
    }
}
