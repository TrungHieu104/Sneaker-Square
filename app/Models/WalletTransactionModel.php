<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One movement of money in or out of a wallet.
 *
 * Append-only: a mistake is corrected by writing the opposite entry, never by
 * editing or deleting one of these. `balance_after` is what the customer saw
 * at that moment, so a later correction cannot rewrite their history.
 */
class WalletTransactionModel extends Model
{
    public const IN = 'in';

    public const OUT = 'out';

    public const TYPE_TOPUP = 'topup';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_REFUND = 'refund';

    public const TYPE_WITHDRAW = 'withdraw';

    public const TYPE_WITHDRAW_REVERSED = 'withdraw_reversed';

    public const TYPE_ADJUSTMENT = 'adjustment';

    /** @var array<string, string> */
    private const TYPE_LABELS = [
        self::TYPE_TOPUP => 'Nạp tiền',
        self::TYPE_PAYMENT => 'Thanh toán đơn hàng',
        self::TYPE_REFUND => 'Hoàn tiền',
        self::TYPE_WITHDRAW => 'Rút về ngân hàng',
        self::TYPE_WITHDRAW_REVERSED => 'Hoàn lại tiền rút bị từ chối',
        self::TYPE_ADJUSTMENT => 'Điều chỉnh từ cửa hàng',
    ];

    protected $table = 'wallet_transactions';

    protected $primaryKey = 'transaction_id';

    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'type',
        'direction',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'entry_hash',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'created_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletModel::class, 'wallet_id', 'wallet_id');
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function isCredit(): bool
    {
        return $this->direction === self::IN;
    }

    /**
     * The amount as the customer reads it on a statement.
     */
    public function signedAmount(): string
    {
        return ($this->isCredit() ? '+' : '-').number_format($this->amount, 0, ',', '.').' đ';
    }
}
