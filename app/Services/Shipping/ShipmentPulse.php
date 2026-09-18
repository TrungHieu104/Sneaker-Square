<?php

namespace App\Services\Shipping;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A marker that says "this order's shipment changed", so an open admin page can
 * be told without asking the database over and over.
 *
 * Deliberately a cache key and not a table: it carries no history, only an
 * opaque token that differs from the last one, and losing it costs a page one
 * refresh.
 */
class ShipmentPulse
{
    /**
     * Long enough that an admin page left open overnight still sees its next
     * change, short enough that the keys clear themselves.
     */
    private const TTL_SECONDS = 86400;

    public function mark(int $orderId): void
    {
        // A token rather than a timestamp: booking and cancelling can land inside
        // the same millisecond, and the second one still has to move the marker.
        Cache::put($this->key($orderId), Str::random(12), self::TTL_SECONDS);
    }

    public function current(int $orderId): string
    {
        return (string) Cache::get($this->key($orderId), '0');
    }

    private function key(int $orderId): string
    {
        return 'shipment.pulse.'.$orderId;
    }
}
