<?php

namespace App\Services\Shipping;

/**
 * A parcel the shop is handing over: where it goes, who receives it, what is
 * inside and how much to collect on delivery.
 */
final class ShipmentOrder
{
    /**
     * @param  array<int, array{name: string, quantity: int, weight: int}>  $items
     */
    public function __construct(
        public readonly string $reference,
        public readonly string $toName,
        public readonly string $toPhone,
        public readonly string $toAddress,
        public readonly int $toDistrictId,
        public readonly string $toWardCode,
        public readonly int $weight,
        public readonly int $insuranceValue,
        public readonly int $codAmount,
        public readonly array $items,
        public readonly ?string $note = null,
    ) {}
}
