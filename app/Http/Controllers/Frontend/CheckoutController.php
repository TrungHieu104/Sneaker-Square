<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\PlaceOrderAction;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\ScreensSessionCart;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Http\Requests\Frontend\CheckoutRequest;
use App\Models\CouponModel as Coupon;
use App\Models\DeliveryInfoModel as Info;
use App\Models\OrderModel as Order;
use App\Models\ProductModel as Product;
use App\Services\CartService;
use App\Services\OrderMailer;
use App\Services\Payment\OrderPayments;
use App\Services\Payment\PaymentNotAllowed;
use App\Services\Shipping\ShippingUnavailable;
use App\Services\ShippingService;
use App\Services\Wallet\InsufficientBalance;
use App\Services\Wallet\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

/**
 * Turning the cart into an order, and handing it to the gateway when one was picked.
 */
class CheckoutController extends Controller
{
    use ScreensSessionCart;
    use SharesStorefrontLayout;

    /**
     * The value `order.order_payment` holds for cash on delivery.
     *
     * The two other values it can hold are the gateway names, so the payment
     * method the customer picked is enough to find the gateway again later.
     */
    private const PAYMENT_ON_DELIVERY = 'cod';

    public function __construct(
        Request $request,
        private readonly CartService $cart,
        private readonly OrderMailer $mailer,
        private readonly OrderPayments $payments,
    ) {
        $this->shareStorefrontLayout();
        View::share('keyword', $request->input('keyword'));
    }

    public function checkout(Request $request)
    {
        if (! Auth::check()) {
            Session::flash('iconMessage', 'warning');

            return redirect()->route('user.login')->with('message', 'Vui lòng đăng nhập trước khi thanh toán.');
        }
        $user_id = Auth::user()->user_id;
        $InfoDeli = Info::where('user_id', $user_id)->get();
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
        if (isset($get['isntEnough']) && $get['isntEnough']) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', 'Opps!!! Sản phẩm trong giỏ hàng của bạn không đủ số lượng trong kho, hãy giảm số lượng mua !');
        }
        // if (!is_array($cart) || count($cart) <= 0) {
        //     Session::flash('iconMessage', 'warning');
        //     return redirect()->route('product.page')->with('message', 'Hãy mua hàng trước nhé.');
        // }
        // The fee depends on the cart, so it is quoted now rather than read
        // back from whatever the address was quoted when it was saved.
        $shippingQuote = app(ShippingService::class)->quoteForAddress($cart, $InfoDeli->firstWhere('info_default', 1));
        $walletBalance = (int) app(WalletService::class)->for(Auth::user())->balance;

        return view('frontend.pages.product.product_checkout', compact('cart', 'coupon_data', 'InfoDeli', 'shippingQuote', 'walletBalance'));

    }

    public function checkoutPOST(CheckoutRequest $request, PlaceOrderAction $placeOrder)
    {
        if (! Auth::check()) {
            return redirect()->route('user.login');
        }

        $user = Auth::user();

        // checkProduct() may drop items from the cart, so it has to run before we read it.
        $check = $this->checkProduct($request);
        $cart = $request->session()->get('cart');
        $coupon = $request->session()->get('coupon_data');
        $coupon = $coupon instanceof Coupon ? $coupon : null;

        if (! is_array($cart) || empty($cart)) {
            $request->session()->forget('coupon_data');

            return redirect('/gio-hang-trong');
        }

        if (isset($check['isRemove']) && $check['isRemove']) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', 'Opps!!! Sản phẩm trong giỏ hàng của bạn vừa bị ẩn, hãy mua sản phẩm khác !');
        }

        if (isset($check['isntEnough']) && $check['isntEnough']) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', 'Opps!!! Sản phẩm trong giỏ hàng của bạn không đủ số lượng trong kho, hãy giảm số lượng mua !');
        }

        $address = Info::where('user_id', $user->user_id)->where('info_default', 1)->first();

        if (! $address) {
            Session::flash('iconMessage', 'warning');

            return redirect()->back()->with('message', 'Hãy chọn địa chỉ nhận hàng !');
        }

        // Every amount is recomputed inside PlaceOrderAction from the database.
        // The thanhtien / deliFee / couVal values posted by the browser are ignored.
        try {
            $order = $placeOrder->execute($user, $cart, $coupon, $address, [
                'payment' => $request->input('payment'),
                'note_customer' => $request->input('note_customer'),
            ]);
        } catch (InsufficientStockException $e) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', $e->userMessage());
        } catch (ShippingUnavailable $e) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', 'Chưa tính được phí vận chuyển cho địa chỉ này, vui lòng kiểm tra lại địa chỉ nhận hàng!');
        } catch (InsufficientBalance $e) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', $e->getMessage());
        }

        session()->forget('cart');
        session()->forget('coupon_data');

        // Cash on delivery and the wallet both leave nowhere to send the
        // customer: one is paid at the door, the other was paid as the order
        // was written.
        if (in_array($order->order_payment, [self::PAYMENT_ON_DELIVERY, PlaceOrderAction::PAY_FROM_WALLET], true)) {
            $this->mailer->sendConfirmation($order);

            return redirect()->route('success.checkout');
        }

        return $this->startGatewayPayment($order);
    }

    /**
     * Sends the customer to the gateway that will take their money.
     *
     * A gateway that cannot be reached leaves the order standing: it keeps
     * its stock until the payment window closes, and the customer can try
     * again or pay another way from the order page in the meantime.
     */
    private function startGatewayPayment(Order $order): RedirectResponse
    {
        try {
            return redirect()->away($this->payments->startAttempt($order));
        } catch (PaymentNotAllowed $e) {
            Session::flash('iconMessage', 'error');

            return redirect()->route('orderBill.checkout', $order->order_code)->with('message', $e->getMessage());
        }
    }

    public function successCheckout()
    {
        return view('frontend.pages.product.success_checkout');
    }

    public function failedCheckout()
    {
        return view('frontend.pages.product.failed_checkout');
    }
}
