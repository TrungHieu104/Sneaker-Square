<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One trip a customer made to a payment gateway for one order.
 *
 * VNPay and MoMo each refuse a transaction code they have seen before, so an
 * order that is paid on the second try needs a second code. The attempt is
 * that code, and it is also what a late or duplicate payment is refunded
 * against: the order can only be paid once, but every attempt that took money
 * must end up either settling it or back in the customer's wallet.
 */
class PaymentAttemptModel extends Model
{
    protected $table = 'payment_attempts';

    protected $primaryKey = 'attempt_id';

    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    /**
     * Money arrived, but the order had already been paid another way or had
     * been cancelled, so it went back to the customer's wallet.
     */
    public const REFUNDED = 'refunded';

    /**
     * Separates the order code from the attempt number. A letter rather than
     * a dash: VNPay documents vnp_TxnRef as alphanumeric only.
     */
    public const SEPARATOR = 'T';

    protected $fillable = [
        'order_id',
        'code',
        'gateway',
        'amount',
        'status',
        'gateway_reference',
        'settled_at',
    ];

    protected $casts = [
        'settled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id', 'order_id');
    }

    public static function codeFor(OrderModel $order, int $attemptId): string
    {
        return $order->order_code.self::SEPARATOR.$attemptId;
    }
}
