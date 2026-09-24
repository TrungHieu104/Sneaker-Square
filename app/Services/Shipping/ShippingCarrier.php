<?php

namespace App\Services\Shipping;

/**
 * A carrier the shop can hand a parcel to.
 *
 * The address lists exist here rather than in a separate lookup service
 * because a district id is only meaningful to the carrier that issued it:
 * quoting GHN with GHTK's ids would price the wrong place.
 */
interface ShippingCarrier
{
    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function provinces(): array;

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function districts(int $provinceId): array;

    /**
     * Wards are keyed by code, not id: GHN's fee endpoint takes the code.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public function wards(int $districtId): array;

    /**
     * @throws ShippingUnavailable when the carrier cannot be reached or refuses
     */
    public function quote(Shipment $shipment): ShippingQuote;

    /**
     * Hands a parcel over. This books a courier, so it is the one call here
     * with a consequence outside the database.
     *
     * @throws ShippingUnavailable
     */
    public function book(ShipmentOrder $order): ShipmentBooking;

    /**
     * @throws ShippingUnavailable
     */
    public function cancel(string $code): void;

    /**
     * Moves a parcel the carrier still holds to another status. Carriers only
     * grant a shop a handful of these; the rest belong to their own staff.
     *
     * @throws ShippingUnavailable
     */
    public function switchStatus(string $code, string $status): void;
}
