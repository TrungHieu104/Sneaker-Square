<?php

namespace App\Services\CartReminders;

use Illuminate\Support\Carbon;

/**
 * A cart one account filled and never checked out, rebuilt from its events.
 */
final class AbandonedCart
{
    /**
     * @param  list<array{pro_id: int, size_id: ?int, color_id: ?int, quantity: int}>  $lines
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $key,
        public readonly array $lines,
        public readonly Carbon $lastActivity,
    ) {}
}
