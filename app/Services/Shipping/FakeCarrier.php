<?php

namespace App\Services\Shipping;

use Carbon\CarbonImmutable;

/**
 * A carrier that answers without leaving the process.
 *
 * Tests bind this so a run neither depends on GHN being up nor pays for a
 * quota, and the demo build uses it when no token is configured.
 */
class FakeCarrier implements ShippingCarrier
{
    /** @var array<int, Shipment> */
    public array $quoted = [];

    public function __construct(
        private int $baseFee = 22000,
        private int $feePerKilo = 5000,
        private int $days = 3,
    ) {}

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function provinces(): array
    {
        return [
            ['id' => 201, 'name' => 'Hà Nội'],
            ['id' => 202, 'name' => 'Hồ Chí Minh'],
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function districts(int $provinceId): array
    {
        return $provinceId === 201
            ? [['id' => 1442, 'name' => 'Quận Ba Đình']]
            : [['id' => 3695, 'name' => 'Thành phố Thủ Đức']];
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    public function wards(int $districtId): array
    {
        return [['code' => '20110', 'name' => 'Phường Tân Định']];
    }

    public function quote(Shipment $shipment): ShippingQuote
    {
        $this->quoted[] = $shipment;

        $kilos = (int) ceil($shipment->weight / 1000);

        return new ShippingQuote(
            fee: $this->baseFee + $kilos * $this->feePerKilo,
            estimated: CarbonImmutable::now()->addDays($this->days)->startOfDay(),
            serviceId: 53321,
        );
    }

    /** @var array<int, ShipmentOrder> */
    public array $booked = [];

    /** @var array<int, string> */
    public array $cancelled = [];

    public function book(ShipmentOrder $order): ShipmentBooking
    {
        $this->booked[] = $order;

        return new ShipmentBooking(
            code: 'FAKE'.str_pad((string) count($this->booked), 3, '0', STR_PAD_LEFT),
            fee: $this->quote(new Shipment(
                $order->toDistrictId,
                $order->toWardCode,
                $order->weight,
                $order->insuranceValue,
            ))->fee,
            estimated: CarbonImmutable::now()->addDays($this->days)->startOfDay(),
        );
    }

    /** @var array<int, array{code: string, status: string}> */
    public array $switched = [];

    public function cancel(string $code): void
    {
        $this->cancelled[] = $code;
    }

    public function switchStatus(string $code, string $status): void
    {
        $this->switched[] = ['code' => $code, 'status' => $status];
    }
}
