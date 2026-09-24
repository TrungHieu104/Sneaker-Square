<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Money on its way into a wallet from a payment gateway.
 *
 * The row exists before the customer leaves for the gateway so the callback
 * has something to land on, and `topup_code` is what the gateway echoes back.
 * The prefix is what tells a top-up callback from an order's.
 */
class WalletTopupModel extends Model
{
    public const PREFIX = 'NAP';

    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    /** @var array<string, string> */
    private const STATUS_LABELS = [
        self::PENDING => 'Chờ thanh toán',
        self::PAID => 'Thành công',
        self::FAILED => 'Thất bại',
    ];

    public const MIN_AMOUNT = 10000;

    public const MAX_AMOUNT = 50000000;

    protected $table = 'wallet_topups';

    protected $primaryKey = 'topup_id';

    protected $fillable = [
        'wallet_id',
        'topup_code',
        'amount',
        'gateway',
        'status',
        'gateway_reference',
        'checkout_url',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WalletModel::class, 'wallet_id', 'wallet_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public static function looksLikeTopupCode(?string $code): bool
    {
        return $code !== null && str_starts_with($code, self::PREFIX);
    }

    /**
     * A code the gateway will accept as its own order id and that no order can
     * collide with: order codes are digits only.
     */
    public static function generateCode(): string
    {
        $date = Carbon::now('Asia/Ho_Chi_Minh')->format('dmY');

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = self::PREFIX.$date.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            if (! self::where('topup_code', $code)->exists()) {
                return $code;
            }
        }

        return self::PREFIX.$date.substr((string) microtime(true), -6);
    }
}
