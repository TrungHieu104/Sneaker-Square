<?php

namespace App\Http\Controllers\Frontend\Concerns;

use App\Services\CartService;
use Illuminate\Http\Request;

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
}
