<?php

namespace App\Services\Shipping;

use Carbon\CarbonImmutable;

/**
 * What the carrier gave back when it accepted a parcel.
 */
final class ShipmentBooking
{
    public function __construct(
        public readonly string $code,
        public readonly int $fee,
        public readonly ?CarbonImmutable $estimated = null,
    ) {}
}
