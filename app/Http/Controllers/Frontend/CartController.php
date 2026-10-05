<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\ScreensSessionCart;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Http\Requests\Frontend\CouponCheckRequest;
use App\Models\CouponModel as Coupon;
use App\Models\ProductModel as Product;
use App\Models\ProductQuantityModel as Quantity;
use App\Models\SizeModel as Size;
use App\Services\CartPricingService;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

/**
 * The cart held in the session, and the coupon applied to it.
 */
class CartController extends Controller
{
    use ScreensSessionCart;
    use SharesStorefrontLayout;

    public function __construct(
        Request $request,
        private readonly CartService $cart,
    ) {
        $this->shareStorefrontLayout();
        View::share('keyword', $request->input('keyword'));
    }

    public function cart(Request $request)
    {
        $get = $this->checkProduct($request);
        $cart = $request->session()->get('cart');
        $coupon_data = $request->session()->get('coupon_data');

        if (! is_array($cart) || empty($cart)) {
            $request->session()->forget('coupon_data');

            return redirect('/gio-hang-trong');
        }

        if (isset($get['isRemove']) && $get['isRemove']) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', 'Opps!!! Sản phẩm trong giỏ hàng của bạn vừa bị ẩn, hãy mua sản phẩm khác !');
        }

        // Shown on the cart itself rather than bounced back to wherever the
        // customer came from: the quantities have just been trimmed and this is
        // the page where that can be seen.
        if (isset($get['isntEnough']) && $get['isntEnough']) {
            Session::flash('iconMessage', 'warning');
            Session::flash('message', 'Số lượng trong giỏ đã được điều chỉnh theo số lượng còn trong kho!');
        }

        return view('frontend.pages.product.product_cart', compact('cart', 'coupon_data'));
    }

    public function addPro(Request $request, string $proSlug = '')
    {
        $quantity = (int) $request['quantity'];
        $color_id = $request['options-color'];
        $size_id = $request['options-size'];

        $product = Product::where('pro_slug', $proSlug)->first();
        $refusal = $product
            ? $this->stockRefusal($request, $product, $proSlug, $color_id, $size_id, $quantity)
            : 'Opps!!! Sản phẩm bạn vừa chọn không có trong hệ thống';

        if ($refusal !== null) {
            return back()->with(['iconMessage' => 'warning', 'message' => $refusal]);
        }

        // Name and price come from the database, never from the request. The cart
        // now holds only what the customer picked: product, size, colour, quantity.
        $this->putInCart($request, [
            'proSlug' => $proSlug,
            'pro_name' => $product->pro_name,
            'quantity' => $quantity,
            'color_id' => $color_id,
            'size_id' => $size_id,
            'pro_price' => app(CartPricingService::class)->unitPrice($product, $color_id, $size_id),
        ]);

        // Two buttons post this form: one to carry on browsing, one to go and
        // pay. Only the second should take the customer off the page.
        if ($request->input('action') === 'stay') {
            Session::flash('iconMessage', 'success');

            return back()->with('message', 'Đã thêm vào giỏ hàng.');
        }

        return redirect('/gio-hang');
    }

    /**
     * Why the variant cannot go into the cart in that amount, or null if it can.
     *
     * Counted against what the cart already holds of it, not against the amount
     * being added alone: two helpings of five each passed a stock of five one
     * at a time.
     */
    private function stockRefusal(Request $request, Product $product, string $proSlug, mixed $colorId, mixed $sizeId, int $quantity): ?string
    {
        $variant = Quantity::where('pro_id', $product->pro_id)->where('color_id', $colorId)->where('size_id', $sizeId)->first();

        if (! $variant) {
            return 'Opps!!! Sản phẩm bạn vừa chọn chưa được nhập trong kho';
        }

        if ((int) $variant->quantity === 0) {
            return 'Opps!!! Sản phẩm bạn vừa chọn đã hết hàng';
        }

        $alreadyInCart = collect($request->session()->get('cart', []))
            ->filter(fn ($item) => ($item['proSlug'] ?? null) == $proSlug
                && ($item['color_id'] ?? null) == $colorId
                && ($item['size_id'] ?? null) == $sizeId)
            ->sum(fn ($item) => (int) ($item['quantity'] ?? 0));

        if ($quantity + $alreadyInCart <= $variant->quantity) {
            return null;
        }

        return $alreadyInCart > 0
            ? 'Opps!!! Giỏ hàng của bạn đã có '.$alreadyInCart.' sản phẩm này, trong kho chỉ còn '.$variant->quantity
            : 'Opps!!! Số lượng bạn chọn vượt quá số lượng trong kho';
    }

    /**
     * A line for a variant already in the cart at the same price is topped up
     * rather than listed twice.
     *
     * @param  array<string, mixed>  $line
     */
    private function putInCart(Request $request, array $line): void
    {
        $cart = $request->session()->get('cart', []);

        foreach ($cart as $key => $item) {
            if ($item['proSlug'] == $line['proSlug'] && $item['pro_name'] == $line['pro_name']
                && $item['size_id'] == $line['size_id'] && $item['color_id'] == $line['color_id']
                && $item['pro_price'] == $line['pro_price']) {
                $cart[$key]['quantity'] += $line['quantity'];
                $request->session()->put('cart', $cart);

                return;
            }
        }

        $cart[] = $line;
        $request->session()->put('cart', $cart);
    }

    public function delPro(Request $request, string $proSlug = '')
    {
        $cart = $request->session()->get('cart');
        $coupon_data = $request->session()->get('coupon_data');
        $giatri_donhang = $request['giatri_donhang'];
        // Matched on the variant, not the product: the same shoe in two colours is
        // two cart lines, and slug alone removes whichever comes first.
        $colorId = $request->input('color_id');
        $sizeId = $request->input('size_id');

        $index = collect($cart)->search(fn ($item) => $item['proSlug'] === $proSlug
            && (string) ($item['color_id'] ?? '') === (string) $colorId
            && (string) ($item['size_id'] ?? '') === (string) $sizeId);

        if ($index !== false) {
            array_splice($cart, $index, 1);
            $request->session()->put('cart', $cart);
        }
        if ($coupon_data !== null && ($coupon_data->coupon_value >= $giatri_donhang)) {
            session()->forget('coupon_data');
            Session::flash('iconMessage', 'warning');

            return redirect()->back()->with('message', 'Cần đặt hàng có giá trị cao hơn để sử dụng mã giảm giá này.');
        }
        Session::flash('iconMessage', 'success');

        return redirect()->route('product.cart')->with('message', 'Xóa sản phẩm thành công!');
    }

    public function delCart(Request $request)
    {
        $request->session()->forget('cart');
        $request->session()->forget('coupon_data');
        Session::flash('iconMessage', 'success');

        return redirect()->route('empty.cart')->with('message', 'Xóa giỏ hàng thành công!');
    }

    public function checkCoupon(CouponCheckRequest $request)
    {
        $today = Carbon::now()->format('Y/m/d');
        $coupon = trim($request['coupon']);
        $giatri_donhang = $request['giatri_donhang'];

        $coupon_data = Coupon::where('coupon_code', $coupon)->first();

        if (! $coupon_data) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', 'Opps!!! Mã giảm giá này không khả dụng.');
        }
        if ($coupon_data->coupon_quantity == 0) {
            Session::flash('iconMessage', 'warning');

            return redirect()->back()->with('message', 'Rất tiếc! Mã giảm giá đã hết lượt sử dụng.');
        }

        if (
            ! ((
                date('Y-m-d', strtotime($coupon_data->coupon_end))
                >=
                date('Y-m-d', strtotime($today))
            ))
        ) {
            Session::flash('iconMessage', 'warning');

            return redirect()->back()->with('message', 'Opps!!! Mã giảm giá đã quá hạn sử dụng.');
        }

        if ($coupon_data->coupon_value >= $giatri_donhang) {
            session()->forget('coupon_data');
            Session::flash('iconMessage', 'warning');

            return redirect()->back()->with('message', 'Cần đặt hàng có giá trị cao hơn để sử dụng mã giảm giá này.');
        }

        session()->put('coupon_data', $coupon_data);

        Session::flash('iconMessage', 'success');
        if ($coupon_data->coupon_condition == 1) {
            return redirect()
                ->back()
                ->with(
                    'message',
                    'Tuyệt quá! Bạn đã sử dụng thành công mã giảm giá '.number_format($coupon_data->coupon_value, 0, ',', '.').'VNĐ'
                );
        } else {
            return redirect()
                ->back()
                ->with(
                    'message',
                    'Tuyệt quá! Bạn đã sử dụng thành công mã giảm giá '.number_format($coupon_data->coupon_value, 0, ',', '.').'%'
                );
        }
    }

    public function removeCoupon(Request $request)
    {
        session()->forget('coupon_data');

        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Mã giảm giá đã được loại bỏ thành công.');
    }

    public function emptycart(Request $request)
    {
        return view('frontend.pages.product.empty_cart');
    }
}
