<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\UserEventModel;
use Illuminate\Database\Query\Builder;
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
        $userId = Auth::id();

        return $this->rememberedFor($this->measured(), $userId === null ? null : (int) $userId, $this->events->visitorId());
    }

    /**
     * The same answer for a shopper named outright, for code that runs
     * outside their request (a queued job has no login, session or cookie).
     *
     * @param  int|null  $measured  the size they measured or chose, as {@see measured()} reads it in their request
     * @return array{size: ?int, source: ?string}
     */
    public function rememberedFor(?int $measured, ?int $userId, ?string $visitor): array
    {
        foreach ([
            self::MEASURED => fn () => $measured,
            self::PURCHASED => fn () => $userId === null ? null : $this->lastPurchasedBy($userId),
            self::CARTED => fn () => $this->lastCarted($userId, $visitor),
        ] as $source => $find) {
            $size = $find();

            if ($size !== null) {
                return ['size' => $size, 'source' => $source];
            }
        }

        return ['size' => null, 'source' => null];
    }

    /**
     * The order lines a customer kept, newest order first.
     */
    public static function keptPurchases(int $userId): Builder
    {
        return DB::table('order_details')
            ->join('order', 'order.order_id', '=', 'order_details.order_id')
            ->where('order.user_id', $userId)
            ->whereNotIn('order.order_status', array_map(fn (OrderStatus $status) => $status->value, self::NOT_KEPT))
            ->whereNotNull('order_details.size')
            ->orderByDesc('order.order_id');
    }

    /**
     * The shoe size of the customer's latest kept pair.
     */
    public function lastPurchasedBy(int $userId): ?int
    {
        return $this->firstShoeSize(self::keptPurchases($userId)->limit(10)->pluck('order_details.size')->all());
    }

    private function lastCarted(?int $userId, ?string $visitor): ?int
    {
        $sizes = UserEventModel::query()
            ->mine($visitor, $userId)
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
