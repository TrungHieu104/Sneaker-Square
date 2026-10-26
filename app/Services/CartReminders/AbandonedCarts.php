<?php

namespace App\Services\CartReminders;

use App\Models\CartReminderModel;
use App\Models\OrderModel;
use App\Models\UserEventModel as Event;
use App\Services\ShopSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Finds the carts that are owed a reminder now.
 *
 * The cart lives in the session, which the server cannot read for a shopper
 * who has left, so it is rebuilt from the add_cart and remove_cart events
 * since the account last placed an order. Only accounts are reminded: a guest
 * has left no address, and checkout asks everyone to log in anyway.
 */
class AbandonedCarts
{
    private const MINUTES_PER_HOUR = 60;

    public function __construct(private readonly ShopSettings $settings) {}

    /**
     * @return list<array{cart: AbandonedCart, step: int}>
     */
    public function due(Carbon $now): array
    {
        $config = $this->settings->cartReminder();

        if (! $config['enabled']) {
            return [];
        }

        $optedOut = $this->optedOut();
        $due = [];

        foreach ($this->open($now->copy()->subDays($config['max_age_days'])) as $cart) {
            $step = in_array($cart->userId, $optedOut, true) ? null : $this->stepFor($cart, $now, $config);

            if ($step !== null) {
                $due[] = ['cart' => $cart, 'step' => $step];
            }
        }

        return $due;
    }

    /**
     * Every account with an email whose cart was last touched after $since
     * and is still waiting to be checked out.
     *
     * @return list<AbandonedCart>
     */
    public function open(Carbon $since): array
    {
        $open = [];

        foreach ($this->candidates($since) as $userId) {
            $cart = $this->stillOpen($userId, $since);

            if ($cart !== null) {
                $open[] = $cart;
            }
        }

        return $open;
    }

    public function stillOpen(int $userId, Carbon $since): ?AbandonedCart
    {
        $cart = $this->openCart($userId);

        return $cart === null || $cart->lastActivity->lt($since) || $this->orderedSince($userId, $cart->lastActivity)
            ? null
            : $cart;
    }

    /**
     * @return list<int>
     */
    public function optedOut(): array
    {
        return DB::table('cart_reminder_opt_outs')->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * The account's cart as it stands: every add since the last order, less
     * what was taken out again.
     */
    public function openCart(int $userId): ?AbandonedCart
    {
        $lastOrder = (int) Event::query()
            ->where('user_id', $userId)
            ->where('event_type', Event::PLACE_ORDER)
            ->max('event_id');

        $events = Event::query()
            ->where('user_id', $userId)
            ->whereIn('event_type', [Event::ADD_CART, Event::REMOVE_CART])
            ->where('event_id', '>', $lastOrder)
            ->orderBy('event_id')
            ->get();

        $lines = [];
        $first = null;
        $last = null;

        foreach ($events as $event) {
            $key = $event->pro_id.'-'.$event->size_id.'-'.$event->color_id;
            $last = $event->created_at;

            if ($event->event_type === Event::REMOVE_CART) {
                unset($lines[$key]);

                continue;
            }

            $first = $lines === [] ? $event->event_id : $first;
            $lines[$key] = [
                'pro_id' => (int) $event->pro_id,
                'size_id' => $event->size_id === null ? null : (int) $event->size_id,
                'color_id' => $event->color_id === null ? null : (int) $event->color_id,
                'quantity' => ($lines[$key]['quantity'] ?? 0) + max(1, (int) ($event->meta['quantity'] ?? 1)),
            ];
        }

        if ($lines === [] || $last === null) {
            return null;
        }

        return new AbandonedCart($userId, $userId.'-'.$first, array_values($lines), $last);
    }

    /**
     * Accounts with cart activity in the window that can be written to.
     *
     * @return list<int>
     */
    private function candidates(Carbon $since): array
    {
        return Event::query()
            ->join('users', 'users.user_id', '=', 'user_events.user_id')
            ->whereIn('event_type', [Event::ADD_CART, Event::REMOVE_CART])
            ->where('user_events.created_at', '>=', $since)
            ->whereNotNull('users.email')
            ->distinct()
            ->pluck('user_events.user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * An order placed without going through the logged checkout still means
     * the cart is no longer abandoned.
     */
    private function orderedSince(int $userId, Carbon $since): bool
    {
        return OrderModel::where('user_id', $userId)->where('created_at', '>', $since)->exists();
    }

    /**
     * The first reminder once the cart has sat for first_hours; the second once
     * it has sat for second_hours, and never sooner after the first than the
     * gap between the two. A coupon offered by hand ends the automatic ones.
     *
     * @param  array{second_enabled: bool, first_hours: int, second_hours: int}  $config
     */
    private function stepFor(AbandonedCart $cart, Carbon $now, array $config): ?int
    {
        $idleHours = $cart->lastActivity->diffInMinutes($now) / self::MINUTES_PER_HOUR;
        $sent = CartReminderModel::where('cart_key', $cart->key)->pluck('sent_at', 'step');

        if ($sent->has(CartReminderModel::OFFER)) {
            return null;
        }

        if (! $sent->has(CartReminderModel::FIRST)) {
            return $idleHours >= $config['first_hours'] ? CartReminderModel::FIRST : null;
        }

        if (! $config['second_enabled'] || $sent->has(CartReminderModel::SECOND)) {
            return null;
        }

        $firstSent = Carbon::parse($sent[CartReminderModel::FIRST]);
        $gap = $config['second_hours'] - $config['first_hours'];

        return $idleHours >= $config['second_hours'] && $firstSent->lte($now->copy()->subHours($gap))
            ? CartReminderModel::SECOND
            : null;
    }
}
