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
 * Nothing here may read a price, a discount or a total out of the request: the
 * form is the customer's to edit. Shipping comes from the chosen address row.
 */
class CartPricingService
{
    /**
     * The price a product actually sells at, mirroring what product_detail
     * displays.
     */
    public function unitPrice(ProductModel $product, $colorId = null, $sizeId = null): int
    {
        $variant = $product->variantFor($colorId, $sizeId);

        return $variant
            ? $variant->sellingPrice($product)
            : $product->sellingPrice();
    }

    /**
     * Rebuilds each line from the session cart, taking price and name from the
     * database.
     *
     * The session is trusted for one thing only: what the customer picked.
     *
     * @param  array<int, array<string, mixed>>  $cart
     * @return array<int, array{product: ProductModel, size_id: ?int, color_id: ?int, size: ?string, color: ?string, quantity: int, unit_price: int, unit_capital_price: int, line_total: int}>
     */
    public function priceCart(array $cart): array
    {
        $lines = [];

        foreach ($cart as $item) {
            $product = ProductModel::where('pro_slug', $item['proSlug'])->first();

            if (! $product) {
                continue;
            }

            $colorId = $item['color_id'] ?? null;
            $sizeId = $item['size_id'] ?? null;
            $quantity = max(1, (int) $item['quantity']);

            $variant = $product->variantFor($colorId, $sizeId);
            $unitPrice = $variant ? $variant->sellingPrice($product) : $product->sellingPrice();

            $lines[] = [
                'product' => $product,
                // Left null rather than cast: a product sold without colours and
                // sizes would look for a variant with id 0 and never find its own.
                'size_id' => $this->identifier($sizeId),
                'color_id' => $this->identifier($colorId),
                'size' => SizeModel::where('size_id', $sizeId)->value('size'),
                'color' => ColorModel::where('color_id', $colorId)->value('color_vn'),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_capital_price' => $variant
                    ? $variant->capitalPrice($product)
                    : (int) $product->capital_price,
                'line_total' => $unitPrice * $quantity,
            ];
        }

        return $lines;
    }

    private function identifier($value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
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
     * `coupon_condition == 1` is a fixed amount off, anything else a percentage.
     * Capped at the subtotal so the total can never go negative.
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
     * Re-checks the coupon at the moment the order is placed: between applying it
     * and paying, it may have run out, expired, or been deleted.
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
