<?php

namespace App\Services\Shipping;

use App\Actions\ReceiveReturnedParcelAction;
use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Models\OrderStatusLogModel;
use App\Models\ShipmentEventModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Records what the carrier says happened to a parcel.
 *
 * Writes are idempotent on purpose: GHN resends a callback up to ten times
 * when it does not get a 200 back, and the same status can legitimately
 * arrive twice from two hubs. The unique key on (order, status, time) is what
 * makes the second delivery a no-op rather than a duplicate timeline row.
 */
class ShipmentTracker
{
    public function __construct(
        private ShipmentPulse $pulse,
        private ReceiveReturnedParcelAction $receiveReturned,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  the callback body, as GHN sent it
     * @return array{order: ?OrderModel, event: ?ShipmentEventModel, recorded: bool}
     */
    public function record(array $payload): array
    {
        // The parcel a customer sends back travels under its own code and must
        // not touch the outbound parcel's status on the order.
        if ($return = $this->returnFor($payload)) {
            return $this->recordReturn($return, $payload);
        }

        $order = $this->orderFor($payload);

        if (! $order) {
            return ['order' => null, 'event' => null, 'recorded' => false];
        }

        $status = trim((string) ($payload['Status'] ?? ''));

        if ($status === '') {
            return ['order' => $order, 'event' => null, 'recorded' => false];
        }

        $happenedAt = $this->timeOf($payload);
        // An order can be handed over more than once. Which parcel an event
        // belongs to is what keeps a cancelled one's history off the next one.
        $shippingCode = $this->text($payload['OrderCode'] ?? null);

        return DB::transaction(function () use ($order, $payload, $status, $happenedAt, $shippingCode) {
            $event = ShipmentEventModel::firstOrCreate(
                [
                    'order_id' => $order->order_id,
                    'shipping_code' => $shippingCode,
                    'status' => $status,
                    'happened_at' => $happenedAt,
                ],
                [
                    'carrier' => 'ghn',
                    'description' => $this->text($payload['Description'] ?? null),
                    'warehouse' => $this->text($payload['Warehouse'] ?? null),
                    'payload' => $payload,
                ]
            );

            $this->syncOrder($order, $payload);

            if ($event->wasRecentlyCreated) {
                $this->pulse->mark((int) $order->order_id);
            }

            return ['order' => $order, 'event' => $event, 'recorded' => $event->wasRecentlyCreated];
        });
    }

    private function returnFor(array $payload): ?OrderReturnModel
    {
        $carrierCode = $this->text($payload['OrderCode'] ?? null);

        if ($carrierCode && $return = OrderReturnModel::where('return_shipping_code', $carrierCode)->first()) {
            return $return;
        }

        $return = OrderReturnModel::forReference($this->text($payload['ClientOrderCode'] ?? null));

        // A return parcel the shop booked on GHN's own dashboard: its first
        // callback ties the code on, unless the shop already cancelled one.
        if ($return && $carrierCode && ! $return->return_shipping_code
            && $return->return_shipping_status !== 'cancel'
            && $return->status === OrderReturnModel::APPROVED) {
            $return->return_shipping_code = $carrierCode;
            $return->save();
        }

        return $return;
    }

    /**
     * @return array{order: ?OrderModel, event: ?ShipmentEventModel, recorded: bool}
     */
    private function recordReturn(OrderReturnModel $return, array $payload): array
    {
        $order = $return->order;
        $status = trim((string) ($payload['Status'] ?? ''));
        $code = $this->text($payload['OrderCode'] ?? null);

        if ($status === '') {
            return ['order' => $order, 'event' => null, 'recorded' => false];
        }

        $happenedAt = $this->timeOf($payload);

        return DB::transaction(function () use ($return, $order, $payload, $status, $code, $happenedAt) {
            $event = ShipmentEventModel::firstOrCreate(
                [
                    'order_id' => $order->order_id,
                    'shipping_code' => $code,
                    'status' => $status,
                    'happened_at' => $happenedAt,
                ],
                [
                    'carrier' => 'ghn',
                    'description' => $this->text($payload['Description'] ?? null),
                    'warehouse' => $this->text($payload['Warehouse'] ?? null),
                    'payload' => $payload,
                ]
            );

            $newest = ShipmentEventModel::where('order_id', $order->order_id)
                ->where('shipping_code', $code)
                ->orderByDesc('happened_at')
                ->value('happened_at');

            $isLatest = ! $newest || ! CarbonImmutable::parse($newest)->greaterThan($happenedAt);

            if ($code !== null && $code === $return->return_shipping_code && $isLatest) {
                $return->return_shipping_status = $status;

                if ($status === 'cancel') {
                    $return->return_shipping_code = null;
                }

                $return->save();
            }

            if ($event->wasRecentlyCreated) {
                $this->pulse->mark((int) $order->order_id);
            }

            return ['order' => $order, 'event' => $event, 'recorded' => $event->wasRecentlyCreated];
        });
    }

    /**
     * GHN's own code is the reliable key once a parcel exists; ClientOrderCode
     * is whatever the shop sent when the shipment was created, which for this
     * shop is the order code printed on the invoice.
     */
    private function orderFor(array $payload): ?OrderModel
    {
        $carrierCode = $this->text($payload['OrderCode'] ?? null);
        $ownCode = $this->text($payload['ClientOrderCode'] ?? null);

        if ($carrierCode) {
            $order = OrderModel::where('order_shipping_code', $carrierCode)->first();

            if ($order) {
                return $order;
            }
        }

        if (! $ownCode) {
            return null;
        }

        $order = OrderModel::where('order_code', $ownCode)->first();

        // First callback for a parcel the shop created on GHN's own dashboard:
        // this is where the two codes get tied together. A cancelled parcel is
        // excluded: the shop released that code on purpose, and a late callback
        // for it would tie the order back to something nobody is carrying.
        if ($order && $carrierCode && ! $order->order_shipping_code && $order->order_shipping_status !== 'cancel') {
            $order->order_shipping_code = $carrierCode;
            $order->save();
        }

        return $order;
    }

    /**
     * The order carries the latest state only. Out-of-order callbacks are
     * common, so an older event must not overwrite a newer one.
     */
    private function syncOrder(OrderModel $order, array $payload): void
    {
        $status = (string) $payload['Status'];
        $happenedAt = $this->timeOf($payload);

        $newest = ShipmentEventModel::where('order_id', $order->order_id)
            ->orderByDesc('happened_at')
            ->value('happened_at');

        if ($newest && CarbonImmutable::parse($newest)->greaterThan($happenedAt)) {
            return;
        }

        $order->order_shipping_status = $status;

        // GHN is the one holding the parcel, so its word on "delivered" is
        // what flips the shop's own delivery flag.
        if ($status === 'delivered') {
            $order->order_delivery_status = 1;
            // The clock for auto-completing the order starts here, and only
            // once: a resent callback must not push the deadline back.
            $order->order_delivered_at ??= $happenedAt;
        }

        // A parcel cancelled on GHN's own dashboard has to free the order the
        // same way cancelling from here does, or the order stays tied to a code
        // nobody is carrying and can never be handed over again.
        if ($status === 'cancel') {
            $order->order_shipping_code = null;
            $order->order_expected_delivery = null;
            $order->order_delivery_status = 0;
        }

        $this->moveOrderAlong($order, $status);

        if ($status === 'returned') {
            $this->receiveReturned->execute($order);
            $order->refresh();
        }
    }

    /**
     * Moves the order itself to match what the carrier just said, and saves
     * whatever else the caller set on the way.
     *
     * Only an order that is still travelling may be moved: one the customer
     * has already confirmed, or one that was cancelled, must not be dragged
     * back onto the road by a late callback.
     */
    private function moveOrderAlong(OrderModel $order, string $status): void
    {
        $target = $this->orderStatusFor($status);

        $travelling = $order->hasStatus(
            OrderStatus::Confirmed,
            OrderStatus::ReadyToShip,
            OrderStatus::Delivering,
            OrderStatus::Delivered,
            OrderStatus::Returning,
        );

        // Cancelling frees an order that never left; it says nothing about one
        // already at the customer's door.
        if ($status === 'cancel' && ! $order->hasStatus(OrderStatus::ReadyToShip, OrderStatus::Delivering)) {
            $target = null;
        }

        if ($target === null || ! $travelling) {
            $order->save();

            return;
        }

        $order->moveTo($target, OrderStatusLogModel::ACTOR_CARRIER, 'GHN: '.GhnStatus::label($status));
    }

    /**
     * What a carrier status means for the order itself.
     *
     * The carrier's own 22 statuses stay in `order_shipping_status`; the order
     * keeps only the handful of milestones the shop acts on. A failed delivery
     * is not one of them: the parcel is still out there, and GHN will try
     * again before deciding to turn back.
     */
    private function orderStatusFor(string $status): ?OrderStatus
    {
        return match ($status) {
            'ready_to_pick', 'picking', 'money_collect_picking' => OrderStatus::ReadyToShip,
            'picked', 'storing', 'transporting', 'sorting', 'delivering', 'money_collect_delivering' => OrderStatus::Delivering,
            'delivered' => OrderStatus::Delivered,
            'return', 'return_transporting', 'return_sorting', 'returning', 'return_fail', 'returned' => OrderStatus::Returning,
            'cancel' => OrderStatus::Confirmed,
            default => null,
        };
    }

    private function timeOf(array $payload): CarbonImmutable
    {
        $raw = $this->text($payload['Time'] ?? null);

        if (! $raw) {
            return CarbonImmutable::now();
        }

        try {
            return CarbonImmutable::parse($raw)->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return CarbonImmutable::now();
        }
    }

    private function text(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
