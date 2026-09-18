<?php

namespace App\Services\Shipping;

/**
 * One parcel as the carrier needs to see it: where it goes, what it weighs,
 * and what it is worth if it is lost.
 */
final class Shipment
{
    public function __construct(
        public readonly int $toDistrictId,
        public readonly string $toWardCode,
        public readonly int $weight,
        public readonly int $insuranceValue = 0,
    ) {}
}
