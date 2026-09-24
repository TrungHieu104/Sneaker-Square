<?php

namespace App\Services;

use App\Models\ColorModel;
use App\Models\CouponModel;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Models\ProductQuantityModel;
use App\Models\SizeModel;
use Illuminate\Support\Facades\DB;

/**
 * Puts back what an order took: the stock of each variant and its coupon use.
 *
 * Callers must hold the order's row lock and check that it has not already
 * been released — nothing here remembers whether it ran before.
 */
class OrderStock
{
    public function release(OrderModel $order): void
    {
        $this->restock($order);

        if ($order->coupon_id) {
            $this->releaseCoupon((int) $order->coupon_id);
        }
    }

    /**
     * The goods only. A customer returning a completed order used the coupon
     * on a sale that happened, so the coupon stays spent.
     */
    public function restock(OrderModel $order): void
    {
        foreach (OrderDetailModel::where('order_id', $order->order_id)->get() as $line) {
            $this->restoreLine($line, (int) $line->quantity);
        }
    }

    /**
     * Only the units the customer actually sent back. A return that names two
     * of the three pairs on a line must not put the third back on the shelf —
     * that pair is still at the customer's house.
     */
    public function restockReturn(OrderReturnModel $return): void
    {
        foreach ($return->items()->with('line')->get() as $item) {
            if ($item->line) {
                $this->restoreLine($item->line, $item->quantity);
            }
        }
    }

    /**
     * Written as a single conditional UPDATE for the same reason the decrement
     * is: two callbacks arriving at once must not both read the old value.
     */
    private function restoreLine(OrderDetailModel $line, int $quantity): void
    {
        $sizeId = $line->size_id ?? SizeModel::where('size', $line->size)->value('size_id');
        $colorId = $line->color_id ?? ColorModel::where('color_vn', $line->color)->value('color_id');

        if (! $sizeId || ! $colorId) {
            return;
        }

        ProductQuantityModel::where('pro_id', $line->pro_id)
            ->where('size_id', $sizeId)
            ->where('color_id', $colorId)
            ->increment('quantity', $quantity);
    }

    /**
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
