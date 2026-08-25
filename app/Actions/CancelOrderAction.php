<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\ColorModel;
use App\Models\CouponModel;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\ProductQuantityModel;
use App\Models\SizeModel;
use Illuminate\Support\Facades\DB;

/**
 * Cancels an unpaid order and puts everything it reserved back.
 *
 * Two defects lived in the code this replaces. It restored stock by walking
 * the session cart, but the cart had already been forgotten before the
 * customer was sent to the gateway — so the loop ran zero times and the stock
 * stayed reserved forever. And it then called forceDelete() on the order with
 * no check that the caller owned it, or that the order was even unpaid.
 *
 * Stock is now restored from `order_details`, which is what the order actually
 * took, using the same atomic increment style as PlaceOrderAction. The order
 * is marked cancelled rather than deleted, so the record survives for the
 * admin's order list and the revenue reports.
 */
class CancelOrderAction
{
    /**
     * @param  bool  $allowPaid  true when a person is cancelling an order they
     *                           already paid for, which the shop refunds by hand.
     *                           A payment gateway callback must never do this.
     * @return bool whether this call is the one that cancelled the order
     */
    public function execute(OrderModel $order, bool $allowPaid = false): bool
    {
        return DB::transaction(function () use ($order, $allowPaid) {
            $fresh = OrderModel::where('order_id', $order->order_id)
                ->lockForUpdate()
                ->first();

            if (! $fresh) {
                return false;
            }

            // A failed payment must not be able to void an order that was paid
            // for — that would be a free way to wipe somebody else's purchase.
            if (! $allowPaid && (int) $fresh->order_payment_status === 1) {
                return false;
            }

            // Safe to run twice: the gateway may send both a redirect and an IPN.
            if ((int) $fresh->order_status === OrderStatus::Cancelled->value) {
                return false;
            }

            foreach (OrderDetailModel::where('order_id', $fresh->order_id)->get() as $line) {
                $this->restoreStock($line);
            }

            if ($fresh->coupon_id) {
                $this->releaseCoupon((int) $fresh->coupon_id);
            }

            $fresh->order_status = OrderStatus::Cancelled->value;
            $fresh->save();

            return true;
        });
    }

    /**
     * Returns one line's quantity to the variant it came from.
     *
     * Written as a single conditional UPDATE for the same reason the decrement
     * is: two callbacks arriving at once must not both read the old value.
     */
    private function restoreStock(OrderDetailModel $line): void
    {
        $sizeId = $line->size_id ?? SizeModel::where('size', $line->size)->value('size_id');
        $colorId = $line->color_id ?? ColorModel::where('color_vn', $line->color)->value('color_id');

        if (! $sizeId || ! $colorId) {
            return;
        }

        ProductQuantityModel::where('pro_id', $line->pro_id)
            ->where('size_id', $sizeId)
            ->where('color_id', $colorId)
            ->increment('quantity', (int) $line->quantity);
    }

    /**
     * Gives the coupon use back.
     *
     * `coupon_used > 0` guards against a counter that has already been reset by
     * hand, which would otherwise go negative.
     */
    private function releaseCoupon(int $couponId): void
    {
        CouponModel::where('coupon_id', $couponId)
            ->where('coupon_used', '>', 0)
            ->update([
                'coupon_quantity' => DB::raw('coupon_quantity + 1'),
                'coupon_used' => DB::raw('coupon_used - 1'),
            ]);
    }
}
