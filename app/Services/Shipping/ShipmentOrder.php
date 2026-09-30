<?php

namespace App\Services\Shipping;

/**
 * A parcel the shop is handing over: where it goes, who receives it, what is
 * inside and how much to collect on delivery. `from` is left null for the
 * usual case, a parcel leaving the shop's own warehouse.
 */
final class ShipmentOrder
{
    /**
     * `$senderPaysCarriage` says which end settles the carriage with GHN, and
     * the two ends swap between an order going out and a parcel coming back:
     * the shop is the sender on one and the receiver on the other. It is
     * always the shop that pays, so this flag follows whichever end it is on.
     *
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
        public readonly ?ShipmentSender $from = null,
        public readonly bool $senderPaysCarriage = true,
    ) {}
}
