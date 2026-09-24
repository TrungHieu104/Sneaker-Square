<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of an order the customer is sending back, and how many of it.
 *
 * A return names its lines rather than its order because a customer who
 * ordered two pairs to try on sends one back and keeps the other. Restocking
 * and the refund are both computed from these rows, never from the order.
 */
class OrderReturnItemModel extends Model
{
    protected $table = 'order_return_items';

    protected $primaryKey = 'return_item_id';

    public $timestamps = false;

    protected $fillable = ['return_id', 'order_details_id', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function return(): BelongsTo
    {
        return $this->belongsTo(OrderReturnModel::class, 'return_id', 'return_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(OrderDetailModel::class, 'order_details_id', 'order_details_id');
    }

    /**
     * What the customer paid for the units being sent back, before the order's
     * coupon is shared out.
     */
    public function lineTotal(): int
    {
        return (int) $this->line->price * $this->quantity;
    }
}
