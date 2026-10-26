<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\UserEventModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The shoe size to put forward for this shopper, and where it came from.
 *
 * A size the shopper measured or chose outranks one read off their history,
 * and a pair they kept outranks a pair they only put in the cart.
 */
class ShoeSizeMemory
{
    public const SESSION_KEY = 'shoe_size';

    public const MEASURED = 'measured';

    public const PURCHASED = 'purchased';

    public const CARTED = 'carted';

    // A pair sent back may have been sent back for not fitting.
    private const NOT_KEPT = [OrderStatus::Cancelled, OrderStatus::Returning, OrderStatus::Returned];

    public function __construct(private readonly UserEventLog $events) {}

    public function measured(): ?int
    {
        $size = Auth::user()?->shoe_size ?? session(self::SESSION_KEY);

        return $size === null ? null : (int) $size;
    }

    /**
     * @return array{size: ?int, source: ?string}
     */
    public function remembered(): array
    {
        foreach ([
            self::MEASURED => fn () => $this->measured(),
            self::PURCHASED => fn () => $this->lastPurchased(),
            self::CARTED => fn () => $this->lastCarted(),
        ] as $source => $find) {
            $size = $find();

            if ($size !== null) {
                return ['size' => $size, 'source' => $source];
            }
        }

        return ['size' => null, 'source' => null];
    }

    private function lastPurchased(): ?int
    {
        $userId = Auth::id();

        if ($userId === null) {
            return null;
        }

        $sizes = DB::table('order_details')
            ->join('order', 'order.order_id', '=', 'order_details.order_id')
            ->where('order.user_id', $userId)
            ->whereNotIn('order.order_status', array_map(fn (OrderStatus $status) => $status->value, self::NOT_KEPT))
            ->whereNotNull('order_details.size')
            ->orderByDesc('order.order_id')
            ->limit(10)
            ->pluck('order_details.size');

        return $this->firstShoeSize($sizes->all());
    }

    private function lastCarted(): ?int
    {
        $userId = Auth::id();

        $sizes = UserEventModel::query()
            ->mine($this->events->visitorId(), $userId === null ? null : (int) $userId)
            ->whereIn('event_type', [UserEventModel::ADD_CART, UserEventModel::PURCHASE])
            ->whereNotNull('user_events.size_id')
            ->join('size', 'size.size_id', '=', 'user_events.size_id')
            ->orderByDesc('event_id')
            ->limit(10)
            ->pluck('size.size');

        return $this->firstShoeSize($sizes->all());
    }

    /**
     * Socks and laces come in sizes too; only a shoe size counts here.
     *
     * @param  array<int, mixed>  $sizes
     */
    private function firstShoeSize(array $sizes): ?int
    {
        foreach ($sizes as $size) {
            if (is_numeric($size) && (int) $size >= FootSize::MIN_SIZE && (int) $size <= FootSize::MAX_SIZE) {
                return (int) $size;
            }
        }

        return null;
    }
}
