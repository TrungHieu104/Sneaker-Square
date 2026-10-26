<?php

namespace App\Http\Controllers\Frontend\Concerns;

use App\Models\CouponModel;
use App\Services\CartPricingService;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * For a controller holding the cart service as `$this->cart`.
 *
 * @property-read CartService $cart
 */
trait ScreensSessionCart
{
    /**
     * Drops anything from the cart the shop can no longer sell.
     *
     * Returns the same shape the callers have always expected: the surviving
     * cart, or a flag array saying why something went missing.
     *
     * @return array<int, array<string, mixed>>|array<string, bool>
     */
    private function checkProduct(Request $request)
    {
        $cart = $request->session()->get('cart');

        if (! is_array($cart) || $cart === []) {
            return ['isEmpty' => true];
        }

        $result = $this->cart->screen($cart);
        $request->session()->put('cart', $result['cart']);

        if ($result['removed']) {
            return ['isRemove' => true];
        }

        if ($result['insufficient']) {
            return ['isntEnough' => true];
        }

        return $result['cart'];
    }

    /**
     * Takes the coupon off a cart that no longer qualifies for it, and says
     * why. Every way of changing the cart ends on the cart or the checkout
     * page, so checking here covers them all.
     */
    private function dropUnusableCoupon(Request $request): void
    {
        $coupon = $request->session()->get('coupon_data');
        $cart = $request->session()->get('cart');

        if (! $coupon instanceof CouponModel || ! is_array($cart)) {
            return;
        }

        $pricing = app(CartPricingService::class);
        $refusal = $pricing->couponRefusal($coupon, $pricing->cartSubtotal($cart));

        if ($refusal === null) {
            return;
        }

        $request->session()->forget('coupon_data');
        Session::flash('iconMessage', 'warning');
        Session::flash('message', 'Mã giảm giá đã được gỡ khỏi giỏ hàng. '.$refusal);
    }
}
