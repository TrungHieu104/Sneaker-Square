<?php

namespace App\Services;

use App\Services\Analytics\EventAggregator;
use App\Services\CustomerSegments\SegmentCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The demo shoppers that SampleDataSeeder writes, and how to take them out.
 *
 * Sample rows sit in the same tables as everything else and nothing in the
 * application treats them differently, so a local copy behaves the way
 * production will. They are recognised only by what they are: a visit whose
 * id no browser could be given, an account at a domain that cannot receive
 * mail, and an order whose code no checkout could generate.
 */
class SampleData
{
    // A real visitor id is letters and digits only; the dash keeps the two apart.
    public const SESSION_PREFIX = 'sample-';

    public const EMAIL_DOMAIN = '@sample.sneakersquare.test';

    public const ORDER_PREFIX = 'SAMPLE-';

    public function __construct(
        private readonly EventAggregator $aggregator,
        private readonly SegmentCalculator $segments,
    ) {}

    /**
     * Removes the sample visits and accounts, then recounts the daily figures
     * of every day they touched.
     *
     * An account somebody went on to place a real order with is kept, since
     * the order points at it.
     *
     * @return array{events: int, reminders: int, orders: int, users: int, kept_users: int}
     */
    public function clear(): array
    {
        $users = DB::table('users')->where('email', 'like', '%'.self::EMAIL_DOMAIN)->pluck('user_id');
        $firstDay = DB::table('user_events')->where('session_id', 'like', self::SESSION_PREFIX.'%')->min('created_at');

        $reminders = DB::table('cart_reminders')->whereIn('user_id', $users);
        $couponIds = (clone $reminders)->whereNotNull('coupon_id')->pluck('coupon_id');
        $reminderCount = $reminders->delete();
        DB::table('coupon')->whereIn('coupon_id', $couponIds)->where('coupon_used', 0)->delete();

        $events = DB::table('user_events')->where('session_id', 'like', self::SESSION_PREFIX.'%')->delete();
        $orders = DB::table('order')->where('order_code', 'like', self::ORDER_PREFIX.'%')->delete();

        $recipients = DB::table('campaign_recipients')->whereIn('user_id', $users);
        $campaignCoupons = (clone $recipients)->whereNotNull('coupon_id')->pluck('coupon_id');
        $recipients->delete();
        DB::table('coupon')->whereIn('coupon_id', $campaignCoupons)->where('coupon_used', 0)->delete();
        DB::table('campaigns')->whereNotExists(fn ($sent) => $sent->select(DB::raw(1))->from('campaign_recipients')->whereColumn('campaign_recipients.campaign_id', 'campaigns.id'))->delete();

        $removable = DB::table('users')
            ->whereIn('user_id', $users)
            ->whereNotExists(fn ($orders) => $orders->select(DB::raw(1))->from('order')->whereColumn('order.user_id', 'users.user_id'));
        $userCount = $removable->delete();
        $this->segments->rebuild();

        if ($firstDay !== null) {
            for ($day = Carbon::parse($firstDay)->startOfDay(); $day->lte(Carbon::today()); $day->addDay()) {
                $this->aggregator->aggregate($day);
            }
        }

        return [
            'events' => $events,
            'reminders' => $reminderCount,
            'orders' => $orders,
            'users' => $userCount,
            'kept_users' => $users->count() - $userCount,
        ];
    }
}
