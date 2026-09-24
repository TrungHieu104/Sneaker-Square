<?php

namespace App\Services\Shipping;

/**
 * Where GHN picks a parcel up when it is not the shop: the customer's door,
 * for goods being sent back.
 */
final class ShipmentSender
{
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $address,
        public readonly int $districtId,
        public readonly string $wardCode,
    ) {}
}
