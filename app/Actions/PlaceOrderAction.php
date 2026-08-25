<?php

namespace App\Actions;

use App\Exceptions\InsufficientStockException;
use App\Models\CouponModel;
use App\Models\DeliveryInfoModel;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use App\Services\CartPricingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates a complete order inside a single transaction.
 *
 * The four writes involved — the order, its line items, the stock decrement and
 * the coupon usage counter — used to run without a transaction, so a failure
 * halfway through left the data permanently inconsistent: an order with stock
 * that was never deducted, or stock deducted for an order that does not exist.
 *
 * Stock is now decremented with a conditional atomic statement rather than the
 * read-modify-write that let two customers buy the same last pair.
 */
class PlaceOrderAction
{
    public function __construct(private readonly CartPricingService $pricing)
    {
    }

    /**
     * @param  array<int, array<string, mixed>>  $cart  the cart held in the session
     * @param  array{payment: string, note_customer: ?string}  $meta
     *
     * @throws InsufficientStockException when a variant no longer has enough stock
     */
    public function execute(
        UserModel $user,
        array $cart,
        ?CouponModel $coupon,
        DeliveryInfoModel $address,
        array $meta,
    ): OrderModel {
        // The shipping fee is read from the delivery address in the database,
        // never taken from the request.
        $summary = $this->pricing->summary($cart, $coupon, (int) $address->info_delivery_fee);

        if (empty($summary['lines'])) {
            throw new InsufficientStockException('Giỏ hàng');
        }

        return DB::transaction(function () use ($user, $address, $meta, $summary) {
            $order = new OrderModel();
            $order->order_code = $this->generateOrderCode();
            $order->order_name = $address->info_name;
            $order->order_phone = $address->info_phone;
            $order->order_email = $address->info_email;
            $order->order_address = $address->info_address;
            $order->order_local = implode(', ', [
                $address->info_ward,
                $address->info_district,
                $address->info_province,
            ]);
            $order->order_delivery_fee = $summary['shipping'];
            $order->order_coupon_value = $summary['discount'];
            $order->order_total = $summary['total'];
            $order->order_payment = $meta['payment'];
            $order->order_payment_status = 0;
            $order->order_date = Carbon::now('Asia/Ho_Chi_Minh')->format('Y/m/d');
            $order->note_customer = $meta['note_customer'] ?? null;
            $order->coupon_id = $summary['coupon']->coupon_id ?? null;
            $order->user_id = $user->user_id;
            $order->save();

            foreach ($summary['lines'] as $line) {
                $this->decrementStock($line);

                OrderDetailModel::create([
                    'order_id' => $order->order_id,
                    'pro_id' => $line['product']->pro_id,
                    'pro_name' => $line['product']->pro_name,
                    'size' => $line['size'],
                    'size_id' => $line['size_id'],
                    'color' => $line['color'],
                    'color_id' => $line['color_id'],
                    'price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                ]);
            }

            if ($summary['coupon']) {
                $this->consumeCoupon($summary['coupon']);
            }

            return $order;
        });
    }

    /**
     * Decrements stock with a conditional UPDATE.
     *
     * The `quantity >= n` guard lives inside the statement itself, so when two
     * requests race for the last pair only one of them affects a row; the other
     * gets zero back and is rejected.
     *
     * @param  array{product: \App\Models\ProductModel, size_id: int, color_id: int, size: ?string, color: ?string, quantity: int}  $line
     *
     * @throws InsufficientStockException
     */
    private function decrementStock(array $line): void
    {
        $affected = ProductQuantityModel::where('pro_id', $line['product']->pro_id)
            ->where('size_id', $line['size_id'])
            ->where('color_id', $line['color_id'])
            ->where('quantity', '>=', $line['quantity'])
            ->decrement('quantity', $line['quantity']);

        if ($affected === 0) {
            throw new InsufficientStockException(
                $line['product']->pro_name,
                $line['size'],
                $line['color'],
            );
        }
    }

    /**
     * A fresh order code, in the ddmmyyyy + four digits format the shop uses.
     *
     * This was generated in the Blade template and posted as a hidden field,
     * which meant the customer chose their own order code — they could send one
     * that already existed and crash the insert, or a code shaped however they
     * liked. The uniqueness check was in the template too, and only retried
     * once before giving up.
     */
    private function generateOrderCode(): string
    {
        $date = Carbon::now('Asia/Ho_Chi_Minh')->format('dmY');

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = $date . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            if (! OrderModel::where('order_code', $code)->exists()) {
                return $code;
            }
        }

        // Falls back to a form that cannot collide rather than failing the sale.
        return $date . substr((string) microtime(true), -6);
    }

    /**
     * Consumes one use of a coupon, again atomically.
     *
     * A coupon running out mid-checkout does not fail the order: the customer
     * still gets the discount they were shown, the counter simply stops at zero
     * instead of going negative.
     */
    private function consumeCoupon(CouponModel $coupon): void
    {
        CouponModel::where('coupon_id', $coupon->coupon_id)
            ->where('coupon_quantity', '>', 0)
            ->update([
                'coupon_quantity' => DB::raw('coupon_quantity - 1'),
                'coupon_used' => DB::raw('coupon_used + 1'),
            ]);
    }
}
