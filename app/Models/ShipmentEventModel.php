<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a parcel's history, as the carrier reported it.
 *
 * Kept append-only. The carrier resends the same event when it does not get a
 * 200 back, and it sends them out of order often enough that the newest row is
 * not always the latest state — order by happened_at, never by id.
 */
class ShipmentEventModel extends Model
{
    protected $table = 'shipment_events';

    protected $primaryKey = 'event_id';

    protected $fillable = [
        'order_id',
        'carrier',
        'shipping_code',
        'status',
        'description',
        'warehouse',
        'happened_at',
        'payload',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
        'payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderModel::class, 'order_id', 'order_id');
    }
}
