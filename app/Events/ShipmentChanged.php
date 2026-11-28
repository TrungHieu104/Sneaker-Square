<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * An order's shipment was booked, cancelled or moved on GHN. Only the order id
 * goes out: the open order page fetches its shipment blocks through the admin
 * route, which checks the reader.
 */
class ShipmentChanged implements ShouldBroadcastNow
{
    public const NAME = 'shipment';

    public const CHANNEL = 'admin.order';

    public function __construct(public readonly int $orderId) {}

    public static function channelFor(int $orderId): string
    {
        return self::CHANNEL.'.'.$orderId;
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(self::channelFor($this->orderId))];
    }

    public function broadcastAs(): string
    {
        return self::NAME;
    }

    /**
     * @return array{order: int}
     */
    public function broadcastWith(): array
    {
        return ['order' => $this->orderId];
    }
}
