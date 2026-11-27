<?php

namespace App\Events;

use App\Services\LiveSocket;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Something the admin bell counts changed: an order or a contact message. The
 * signal carries nothing; each open admin page fetches the counts itself.
 */
class AdminNoticesChanged implements ShouldBroadcastNow
{
    public const NAME = 'notices';

    public const CHANNEL = 'admin.notices';

    /**
     * Hooked to every write of a model the bell counts, so no caller has to remember it.
     */
    public static function announce(): void
    {
        app(LiveSocket::class)->send(new self);
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(self::CHANNEL)];
    }

    public function broadcastAs(): string
    {
        return self::NAME;
    }

    /**
     * @return array<string, never>
     */
    public function broadcastWith(): array
    {
        return [];
    }
}
