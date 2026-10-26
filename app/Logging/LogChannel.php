<?php

namespace App\Logging;

/**
 * The log files kept apart from laravel.log, so the trail of one kind of
 * incident can be read without wading through the rest.
 */
final class LogChannel
{
    // Gateway callbacks, wallet movements, money that could not be applied.
    public const PAYMENT = 'payment';

    // GHN webhooks, parcel bookings and every GHN API call that failed.
    public const SHIPPING = 'shipping';

    // Every email that left the shop, and every one that failed to.
    public const MAIL = 'mail';

    // A week is enough to answer a complaint, short enough not to pile up.
    public const DAYS_KEPT = 7;
}
