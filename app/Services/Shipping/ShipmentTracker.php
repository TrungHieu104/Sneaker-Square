<?php

namespace App\Services\Shipping;

use App\Models\OrderModel;
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
    public function __construct(private ShipmentPulse $pulse) {}

    /**
     * @param  array<string, mixed>  $payload  the callback body, as GHN sent it
     * @return array{order: ?OrderModel, event: ?ShipmentEventModel, recorded: bool}
     */
    public function record(array $payload): array
    {
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
        }

        // A parcel cancelled on GHN's own dashboard has to free the order the
        // same way cancelling from here does, or the order stays tied to a code
        // nobody is carrying and can never be handed over again.
        if ($status === 'cancel') {
            $order->order_shipping_code = null;
            $order->order_expected_delivery = null;
        }

        $order->save();
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
