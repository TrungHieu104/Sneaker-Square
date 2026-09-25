<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\DeliveryInfoModel;
use App\Models\OrderModel;
use App\Models\OrderReturnItemModel;
use App\Models\OrderReturnModel;
use App\Models\OrderStatusLogModel;
use App\Services\Shipping\Shipment;
use App\Services\Shipping\ShipmentBooking;
use App\Services\Shipping\ShipmentPulse;
use App\Services\Shipping\ShipmentOrder;
use App\Services\Shipping\ShipmentSender;
use App\Services\Shipping\ShippingCarrier;
use App\Services\Shipping\ShippingQuote;
use App\Services\Shipping\ShippingUnavailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Turns a cart and an address into a shipping price.
 *
 * The fee depends on what is in the cart, so it cannot be settled when the
 * address is saved and read back later: adding a second pair of shoes changes
 * the parcel. Every screen that shows a fee asks for it here, and so does the
 * code that writes the order, which is what stops a browser from posting its
 * own number.
 */
class ShippingService
{
    /**
     * GHN refuses a parcel of nothing, and a cart of shoelaces really does
     * weigh almost nothing.
     */
    private const MINIMUM_WEIGHT = 100;

    public function __construct(
        private ShippingCarrier $carrier,
        private CartPricingService $pricing,
        private ShipmentPulse $pulse,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $cart
     *
     * @throws ShippingUnavailable
     */
    public function quoteForCart(array $cart, int $districtId, string $wardCode): ShippingQuote
    {
        $lines = $this->pricing->priceCart($cart);

        return $this->carrier->quote(new Shipment(
            toDistrictId: $districtId,
            toWardCode: $wardCode,
            weight: $this->weightOf($lines),
            insuranceValue: $this->pricing->subtotal($lines),
        ));
    }

    /**
     * Null when the parcel cannot be priced: no address chosen yet, an address
     * saved before the carrier ids existed, or GHN refusing to answer.
     *
     * There is deliberately no flat fee to stand in. Every parcel leaves on a
     * GHN service, so a number invented here is one the shop cannot honour and
     * the customer reads as final.
     *
     * @param  array<int, array<string, mixed>>  $cart
     */
    public function quoteForAddress(array $cart, ?DeliveryInfoModel $address): ?ShippingQuote
    {
        if (! $address || ! $address->info_district_id || ! $address->info_ward_code) {
            return null;
        }

        try {
            return $this->quoteForCart($cart, (int) $address->info_district_id, (string) $address->info_ward_code);
        } catch (ShippingUnavailable $e) {
            Log::warning('Shipping quote failed', [
                'address' => $address->info_id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function weightOf(array $lines): int
    {
        $grams = 0;

        foreach ($lines as $line) {
            $grams += (int) $line['product']->pro_weight * (int) $line['quantity'];
        }

        return max(self::MINIMUM_WEIGHT, $grams);
    }

    /**
     * Hands one order's parcel to the carrier and records the code it gives
     * back, which is what later status callbacks are matched on.
     *
     * @throws ShippingUnavailable
     */
    public function book(OrderModel $order): ShipmentBooking
    {
        if ($order->order_shipping_code) {
            throw new ShippingUnavailable('Đơn hàng này đã có vận đơn '.$order->order_shipping_code.'.');
        }

        if (! $order->order_district_id || ! $order->order_ward_code) {
            throw new ShippingUnavailable('Đơn hàng thiếu mã quận/huyện hoặc phường/xã.');
        }

        $lines = $order->orderDetail;

        if ($lines->isEmpty()) {
            throw new ShippingUnavailable('Đơn hàng không có sản phẩm nào.');
        }

        $booking = $this->carrier->book(new ShipmentOrder(
            reference: $order->order_code,
            toName: (string) $order->order_name,
            toPhone: (string) $order->order_phone,
            toAddress: trim($order->order_address.', '.$order->order_local, ', '),
            toDistrictId: (int) $order->order_district_id,
            toWardCode: (string) $order->order_ward_code,
            weight: $this->weightOfOrder($order),
            // The goods, not the postage. The quote declared the same number, so
            // declaring the total here would bill the shop a fee it never
            // charged the customer, and insure a shipping fee against loss.
            insuranceValue: $this->goodsValueOf($order),
            // Only an unpaid order still collects money at the door, and there
            // the customer really does hand over goods plus shipping.
            codAmount: $order->order_payment_status ? 0 : (int) $order->order_total,
            items: $lines->map(fn ($line) => [
                'name' => (string) $line->pro_name,
                'quantity' => (int) $line->quantity,
                'weight' => (int) ($line->product->pro_weight ?? self::MINIMUM_WEIGHT),
            ])->all(),
            note: $order->note_customer,
        ));

        $order->order_shipping_code = $booking->code;
        // A fresh parcel starts with no history of its own; leaving the old
        // status would show the previous one's outcome against this code.
        $order->order_shipping_status = null;

        if ($booking->estimated) {
            $order->order_expected_delivery = $booking->estimated->toDateString();
        }

        $order->moveTo(OrderStatus::ReadyToShip, OrderStatusLogModel::ACTOR_ADMIN, 'Tạo vận đơn '.$booking->code);
        $this->pulse->mark((int) $order->order_id);

        return $booking;
    }

    /**
     * What the parcel is worth: the lines as they were priced when the order
     * was written, before any coupon.
     */
    private function goodsValueOf(OrderModel $order): int
    {
        return (int) $order->orderDetail->sum(
            fn ($line) => (int) $line->price * (int) $line->quantity
        );
    }

    /**
     * @throws ShippingUnavailable
     */
    public function cancelBooking(OrderModel $order): void
    {
        if (! $order->order_shipping_code) {
            throw new ShippingUnavailable('Đơn hàng chưa có vận đơn để huỷ.');
        }

        $this->carrier->cancel($order->order_shipping_code);

        // The code is released so the order can be handed over again. Its
        // history is not lost: shipment_events hang off the order, not off the
        // code, and a late callback for the cancelled parcel still finds the
        // order through ClientOrderCode.
        $order->order_shipping_code = null;
        $order->order_expected_delivery = null;
        $order->order_shipping_status = 'cancel';
        $order->order_delivery_status = 0;
        $order->moveTo(OrderStatus::Confirmed, OrderStatusLogModel::ACTOR_ADMIN, 'Huỷ vận đơn');
        $this->pulse->mark((int) $order->order_id);
    }

    /**
     * Lets go of the parcel that was to bring a return home, so the shop can
     * book it again.
     *
     * The carrier's own callback releases the code when a parcel is cancelled,
     * but a shop that cancelled on GHN's dashboard while its webhook could not
     * be reached is left holding a code for a parcel nobody will collect. Then
     * $hoiHang is false and only this side is cleaned up — which is why the
     * screen says plainly that it does not cancel anything at GHN.
     *
     * @throws ShippingUnavailable
     */
    public function releaseReturnBooking(OrderReturnModel $return, bool $hoiHang = true): void
    {
        if (! $return->return_shipping_code) {
            throw new ShippingUnavailable('Yêu cầu trả hàng này chưa có vận đơn.');
        }

        if ($hoiHang) {
            $this->carrier->cancel($return->return_shipping_code);
        }

        // Released, not erased: shipment_events hang off the order, so a late
        // callback for the cancelled parcel still finds its own history.
        $return->return_shipping_code = null;
        $return->return_shipping_status = 'cancel';
        $return->save();
        $this->pulse->mark((int) $return->order_id);
    }

    /**
     * Books the parcel that brings a returned order back: picked up at the
     * customer's address, delivered to the shop's. The shop pays the fee and
     * nothing is collected, so a refund is never netted against postage.
     *
     * @throws ShippingUnavailable
     */
    public function bookReturn(OrderReturnModel $return): ShipmentBooking
    {
        if ($return->status !== OrderReturnModel::APPROVED) {
            throw new ShippingUnavailable('Chỉ yêu cầu trả hàng đã duyệt mới tạo được vận đơn trả hàng.');
        }

        if ($return->return_shipping_code) {
            throw new ShippingUnavailable('Yêu cầu trả hàng này đã có vận đơn '.$return->return_shipping_code.'.');
        }

        $order = $return->order;

        if (! $order->order_district_id || ! $order->order_ward_code) {
            throw new ShippingUnavailable('Đơn hàng thiếu mã quận/huyện hoặc phường/xã của khách.');
        }

        // The parcel is what the customer is sending back, not what they bought:
        // a return of one of two pairs is one box, and GHN prices the carriage
        // and the insurance from the numbers it is given.
        $dongTra = $return->items()->with('line.product')->get();

        $booking = $this->carrier->book(new ShipmentOrder(
            reference: $return->reference(),
            toName: (string) config('services.ghn.from_name'),
            toPhone: (string) config('services.ghn.from_phone'),
            toAddress: (string) config('services.ghn.from_address'),
            toDistrictId: (int) config('services.ghn.from_district_id'),
            toWardCode: (string) config('services.ghn.from_ward_code'),
            weight: $this->weightOfReturn($dongTra),
            insuranceValue: (int) $dongTra->sum(fn ($muc) => $muc->lineTotal()),
            codAmount: 0,
            items: $dongTra->map(fn ($muc) => [
                'name' => (string) $muc->line?->pro_name,
                'quantity' => (int) $muc->quantity,
                'weight' => (int) ($muc->line?->product?->pro_weight ?? self::MINIMUM_WEIGHT),
            ])->all(),
            note: 'Hàng trả lại của đơn '.$order->order_code,
            from: new ShipmentSender(
                name: (string) $order->order_name,
                phone: (string) $order->order_phone,
                address: trim($order->order_address.', '.$order->order_local, ', '),
                districtId: (int) $order->order_district_id,
                wardCode: (string) $order->order_ward_code,
            ),
        ));

        $return->return_shipping_code = $booking->code;
        $return->return_shipping_status = null;
        $return->save();
        $this->pulse->mark((int) $order->order_id);

        return $booking;
    }

    /**
     * @param  Collection<int, OrderReturnItemModel>  $items
     */
    private function weightOfReturn(Collection $items): int
    {
        $grams = 0;

        foreach ($items as $muc) {
            $grams += (int) ($muc->line?->product?->pro_weight ?? 0) * (int) $muc->quantity;
        }

        return max(self::MINIMUM_WEIGHT, $grams);
    }

    public function weightOfOrder(OrderModel $order): int
    {
        $grams = 0;

        foreach ($order->orderDetail as $line) {
            $grams += (int) ($line->product->pro_weight ?? 0) * (int) $line->quantity;
        }

        return max(self::MINIMUM_WEIGHT, $grams);
    }
}
