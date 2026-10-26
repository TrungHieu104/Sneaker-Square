<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * The demo shoppers that SampleDataSeeder writes, and how to take them out.
 *
 * Sample rows sit in the same tables as everything else and nothing in the
 * application treats them differently, so a local copy behaves the way
 * production will. They are recognised only by what they are: a visit whose
 * id no browser could be given, and an account at a domain that cannot
 * receive mail.
 */
class SampleData
{
    // A real visitor id is letters and digits only; the dash keeps the two apart.
    public const SESSION_PREFIX = 'sample-';

    public const EMAIL_DOMAIN = '@sample.sneakersquare.test';

    /**
     * Removes the sample visits and accounts.
     *
     * An account somebody went on to place a real order with is kept, since
     * the order points at it.
     *
     * @return array{events: int, reminders: int, users: int, kept_users: int}
     */
    public function clear(): array
    {
        $users = DB::table('users')->where('email', 'like', '%'.self::EMAIL_DOMAIN)->pluck('user_id');

        $reminders = DB::table('cart_reminders')->whereIn('user_id', $users);
        $couponIds = (clone $reminders)->whereNotNull('coupon_id')->pluck('coupon_id');
        $reminderCount = $reminders->delete();
        DB::table('coupon')->whereIn('coupon_id', $couponIds)->where('coupon_used', 0)->delete();

        $events = DB::table('user_events')->where('session_id', 'like', self::SESSION_PREFIX.'%')->delete();

        $removable = DB::table('users')
            ->whereIn('user_id', $users)
            ->whereNotExists(fn ($orders) => $orders->select(DB::raw(1))->from('order')->whereColumn('order.user_id', 'users.user_id'));
        $userCount = $removable->delete();

        return [
            'events' => $events,
            'reminders' => $reminderCount,
            'users' => $userCount,
            'kept_users' => $users->count() - $userCount,
        ];
    }
}
