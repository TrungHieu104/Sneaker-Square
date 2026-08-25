<?php

namespace App\Services;

use App\Models\ColorModel;
use App\Models\CouponModel;
use App\Models\ProductModel;
use App\Models\SizeModel;
use Illuminate\Support\Carbon;

/**
 * Works out every amount on an order from the data in the database.
 *
 * Product prices, the discount and the order total all used to arrive in the
 * request from the browser, so editing the form was enough to buy at any price.
 * All of that arithmetic now lives here and reads from the database only.
 *
 * The shipping fee still comes from `delivery_info.info_delivery_fee` on the
 * chosen address — the existing scheme is deliberately unchanged and will be
 * replaced by a carrier API later. What changed is that the value is read from
 * the database instead of accepted from the request.
 */
class CartPricingService
{
    /**
     * The price a product actually sells at: the sale price when one is set.
     *
     * This mirrors the rule product_detail.blade.php displays.
     */
    public function unitPrice(ProductModel $product): int
    {
        return (int) ($product->pro_price_sale != 0
            ? $product->pro_price_sale
            : $product->pro_price);
    }

    /**
     * Rebuilds each line from the session cart, taking price and name from the database.
     *
     * The session is now trusted for one thing only: what the customer picked —
     * which product, which size, which colour, and how many.
     *
     * @param  array<int, array<string, mixed>>  $cart
     * @return array<int, array{product: ProductModel, size_id: int, color_id: int, size: ?string, color: ?string, quantity: int, unit_price: int, line_total: int}>
     */
    public function priceCart(array $cart): array
    {
        $lines = [];

        foreach ($cart as $item) {
            $product = ProductModel::where('pro_slug', $item['proSlug'])->first();

            if (! $product) {
                continue;
            }

            $quantity = max(1, (int) $item['quantity']);
            $unitPrice = $this->unitPrice($product);

            $lines[] = [
                'product' => $product,
                'size_id' => (int) $item['size_id'],
                'color_id' => (int) $item['color_id'],
                'size' => SizeModel::where('size_id', $item['size_id'])->value('size'),
                'color' => ColorModel::where('color_id', $item['color_id'])->value('color_vn'),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $quantity,
            ];
        }

        return $lines;
    }

    /**
     * @param  array<int, array{line_total: int}>  $lines
     */
    public function subtotal(array $lines): int
    {
        return array_sum(array_column($lines, 'line_total'));
    }

    /**
     * The amount discounted.
     *
     * `coupon_condition == 1` means a fixed amount off, anything else means a
     * percentage — matching product_checkout.blade.php. The result is capped at
     * the goods subtotal so the order total can never go negative.
     */
    public function discountFor(?CouponModel $coupon, int $subtotal): int
    {
        if (! $coupon) {
            return 0;
        }

        $discount = $coupon->coupon_condition == 1
            ? (int) $coupon->coupon_value
            : (int) round($subtotal * ((int) $coupon->coupon_value) / 100);

        return max(0, min($discount, $subtotal));
    }

    /**
     * Re-checks the coupon at the moment the order is placed.
     *
     * It was already checked when the customer applied it, but in between it may
     * have run out of uses, expired, or been deleted by an administrator.
     */
    public function couponIsUsable(?CouponModel $coupon, int $subtotal): bool
    {
        if (! $coupon) {
            return false;
        }

        $fresh = CouponModel::find($coupon->coupon_id);

        if (! $fresh || $fresh->coupon_quantity <= 0) {
            return false;
        }

        if ($fresh->coupon_end && Carbon::parse($fresh->coupon_end)->endOfDay()->isPast()) {
            return false;
        }

        // A fixed-amount coupon must be smaller than the subtotal, same as when applied.
        if ($fresh->coupon_condition == 1 && $fresh->coupon_value >= $subtotal) {
            return false;
        }

        return true;
    }

    /**
     * Every amount on the order, computed from the database.
     *
     * @param  array<int, array<string, mixed>>  $cart  the cart held in the session
     * @return array{lines: array<int, array<string, mixed>>, subtotal: int, discount: int, shipping: int, total: int, coupon: ?CouponModel}
     */
    public function summary(array $cart, ?CouponModel $coupon, int $shippingFee): array
    {
        $lines = $this->priceCart($cart);
        $subtotal = $this->subtotal($lines);

        $usableCoupon = $this->couponIsUsable($coupon, $subtotal) ? $coupon : null;
        $discount = $this->discountFor($usableCoupon, $subtotal);
        $shipping = max(0, $shippingFee);

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => $subtotal - $discount + $shipping,
            'coupon' => $usableCoupon,
        ];
    }
}
