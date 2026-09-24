<?php

namespace App\Actions;

use App\Exceptions\InsufficientStockException;
use App\Models\CouponModel;
use App\Models\DeliveryInfoModel;
use App\Models\OrderDetailModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use App\Models\WalletTransactionModel;
use App\Services\CartPricingService;
use App\Services\Shipping\ShippingUnavailable;
use App\Services\ShippingService;
use App\Services\Wallet\InsufficientBalance;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates a complete order inside a single transaction.
 *
 * Four writes have to stand or fall together: the order, its line items, the
 * stock decrement and the coupon usage counter.
 */
class PlaceOrderAction
{
    public const PAY_FROM_WALLET = 'wallet';

    public function __construct(
        private readonly CartPricingService $pricing,
        private readonly ShippingService $shipping,
        private readonly WalletService $wallets,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $cart  the cart held in the session
     * @param  array{payment: string, note_customer: ?string}  $meta
     *
     * @throws InsufficientStockException when a variant no longer has enough stock
     * @throws InsufficientBalance when paying from a wallet that cannot cover it
     */
    public function execute(
        UserModel $user,
        array $cart,
        ?CouponModel $coupon,
        DeliveryInfoModel $address,
        array $meta,
    ): OrderModel {
        // Quoted here, from the cart and the address, because the parcel is
        // only final at this point and because a fee posted by the form is a
        // fee the customer chose.
        $quote = $this->shipping->quoteForAddress($cart, $address);

        if (! $quote) {
            throw new ShippingUnavailable('No carrier quote for address '.$address->info_id);
        }
        $summary = $this->pricing->summary($cart, $coupon, $quote->fee);

        if (empty($summary['lines'])) {
            throw new InsufficientStockException('Giỏ hàng');
        }

        return DB::transaction(function () use ($user, $address, $meta, $summary, $quote) {
            $order = new OrderModel;
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
            // Copied onto the order, not read back through the address: the
            // customer may edit or delete that address tomorrow.
            $order->order_district_id = $address->info_district_id;
            $order->order_ward_code = $address->info_ward_code;
            $order->order_delivery_fee = $summary['shipping'];
            $order->order_expected_delivery = $quote->estimated?->toDateString();
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
                    'capital_price' => $line['unit_capital_price'],
                    'quantity' => $line['quantity'],
                ]);
            }

            if ($summary['coupon']) {
                $this->consumeCoupon($summary['coupon']);
            }

            if ($order->order_payment === self::PAY_FROM_WALLET) {
                $this->payFromWallet($user, $order);
            }

            return $order;
        });
    }

    /**
     * Takes the total out of the customer's wallet.
     *
     * Inside the order's transaction on purpose: if the balance will not cover
     * it the stock decrement and the coupon go back with it, and the customer
     * is told before anything exists. There is no gateway to wait for, so the
     * order is paid the moment it is placed.
     *
     * @throws InsufficientBalance
     */
    private function payFromWallet(UserModel $user, OrderModel $order): void
    {
        $this->wallets->debit(
            $this->wallets->for($user),
            (int) $order->order_total,
            WalletTransactionModel::TYPE_PAYMENT,
            'Thanh toán đơn hàng '.$order->order_code,
            WalletService::REF_ORDER,
            (int) $order->order_id,
        );

        $order->order_payment_status = 1;
        $order->order_payment_time = Carbon::now('Asia/Ho_Chi_Minh');
        $order->save();
    }

    /**
     * Decrements stock with a conditional UPDATE.
     *
     * The `quantity >= n` guard lives inside the statement, so when two requests
     * race for the last pair only one of them affects a row.
     *
     * @param  array{product: ProductModel, size_id: ?int, color_id: ?int, size: ?string, color: ?string, quantity: int}  $line
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
     * Generated here rather than posted from the form, or the customer picks
     * their own.
     */
    private function generateOrderCode(): string
    {
        $date = Carbon::now('Asia/Ho_Chi_Minh')->format('dmY');

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = $date.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            if (! OrderModel::where('order_code', $code)->exists()) {
                return $code;
            }
        }

        // Falls back to a form that cannot collide rather than failing the sale.
        return $date.substr((string) microtime(true), -6);
    }

    /**
     * Consumes one use of a coupon, again atomically.
     *
     * Running out mid-checkout does not fail the order: the customer keeps the
     * discount they were shown, the counter simply stops at zero.
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
