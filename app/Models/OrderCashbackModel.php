<?php

namespace App\Models;

use App\Enums\CustomerSegment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What an order was promised back into the wallet, fixed when it was placed:
 * the customer changing group or the admin changing the rates afterwards does
 * not alter a promise already shown at checkout.
 */
class OrderCashbackModel extends Model
{
    protected $table = 'order_cashbacks';

    protected $primaryKey = 'order_id';

    public $incrementing = false;

    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'segment', 'percent', 'cap', 'amount', 'paid_at'];

    protected $casts = [
        'segment' => CustomerSegment::class,
        'paid_at' => 'datetime',
    ];

    /** @return BelongsTo<OrderModel, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id', 'order_id');
    }
}
