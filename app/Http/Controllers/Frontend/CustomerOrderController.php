<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\CancelOrderAction;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\SharesStorefrontLayout;
use App\Models\CouponModel as Coupon;
use App\Models\OrderDetailModel as OrderDetail;
use App\Models\OrderModel as Order;
use App\Models\OrderStatusLogModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

/**
 * A customer's own order: the order page, its printable bill, and cancelling it.
 */
class CustomerOrderController extends Controller
{
    use SharesStorefrontLayout;

    public function __construct(
        Request $request,
        private readonly CancelOrderAction $cancelOrder,
    ) {
        $this->shareStorefrontLayout();
        View::share('keyword', $request->input('keyword'));
    }

    public function orderBill(string $order_code = '')
    {
        if (Auth::check()) {
            // Someone else's order answers exactly like one that does not
            // exist, so the page cannot be used to find out which codes are real.
            $order = Order::where('order_code', $order_code)
                ->where('user_id', Auth::id())
                ->first();
            if ($order) {
                $coupon_data = Coupon::where('coupon_id', $order->coupon_id)->first();
                $orderDetail = OrderDetail::where('order_id', $order->order_id)->get();

                return view('frontend.pages.product.order_bill', compact('order', 'coupon_data', 'orderDetail'));
            } else {
                Session::flash('iconMessage', 'warning');

                return redirect()->back()->with('message', 'Không tồn tại đơn hàng này !');
            }
        } else {
            Session::flash('iconMessage', 'warning');

            return redirect()->route('user.login')->with('message', 'Vui lòng đăng nhập trước khi xem đơn hàng.');
        }
    }

    public function printBill($order_code)
    {
        if (Auth::check()) {
            $check_current_order = Order::where('order_code', $order_code)
                ->where('user_id', Auth::id())
                ->first();
            if (! $check_current_order) {
                Session::flash('iconMessage', 'warning');

                return redirect()->back()->with('message', 'Bạn chỉ được quyền xuất hóa đơn của bạn !');
            }
            $pdf = App::make('dompdf.wrapper');
            $pdf->loadHTML($this->print_order_convert($order_code));

            return $pdf->stream();
        } else {
            Session::flash('iconMessage', 'warning');

            return redirect()->route('user.login')->with('message', 'Vui lòng đăng nhập trước xuất hóa đơn.');
        }
    }

    public function print_order_convert($order_code)
    {
        $order = Order::where('order_code', $order_code)->first();
        $od = OrderDetail::where('order_id', $order->order_id)->get();

        return view('frontend.pages.product.pdf.print_bill', compact('od', 'order'));
    }

    /**
     * A customer cancelling their own order.
     *
     * Two things were missing. Anyone signed in could cancel any order by
     * guessing its code, because the order was never checked against the
     * caller. And the goods stayed reserved: the order was marked cancelled
     * without ever putting the stock back.
     */
    public function cancelOrder(Request $request, $order_code)
    {
        if (! Auth::check()) {
            return redirect()->route('user.login');
        }

        $order = Order::where('order_code', $order_code)
            ->where('user_id', Auth::user()->user_id)
            ->first();

        if (! $order) {
            Session::flash('iconMessage', 'warning');

            return redirect()->back()->with('message', 'Không tìm thấy đơn hàng này trong tài khoản của bạn !');
        }

        $reason = trim((string) $request->input('inputCancelOrder'));

        // Before the shop has looked at it, the order is still the customer's
        // to call off. Once it has been confirmed the goods may already be
        // packed, so from there the customer asks and the shop decides; once
        // the parcel has left, not even that.
        if ($order->hasStatus(OrderStatus::New)) {
            // Cancelling an order they paid for is a refund, not a fraud
            // attempt, so this call is allowed to touch paid orders.
            if (! $this->cancelOrder->execute($order, allowPaid: true, actor: OrderStatusLogModel::ACTOR_CUSTOMER, note: $reason ?: null)) {
                Session::flash('iconMessage', 'warning');

                return redirect()->back()->with('message', 'Đơn hàng này đã được hủy trước đó.');
            }

            $order->note_customer = 'Lí do hủy đơn: '.$reason;
            $order->save();

            Session::flash('iconMessage', 'success');

            // CancelOrderAction has already put the money in the wallet.
            if ((int) $order->order_payment_status === 1) {
                Session::flash('text', 'Số tiền đã thanh toán đã được hoàn vào SPay của bạn.');
            }

            return redirect()->back()->with('message', ' Đã hủy đơn hàng !');
        }

        if (! $order->canRequestCancel()) {
            Session::flash('iconMessage', 'warning');

            return redirect()->back()->with('message', 'Đơn hàng đã rời kho, không hủy được nữa. Bạn có thể từ chối nhận hàng khi shipper giao.');
        }

        $order->order_cancel_reason = $reason;
        $order->moveTo(OrderStatus::CancelRequested, OrderStatusLogModel::ACTOR_CUSTOMER, $reason ?: null);

        Session::flash('iconMessage', 'success');

        return redirect()->back()->with([
            'message' => ' Đã gửi yêu cầu hủy đơn !',
            'text' => 'Shop sẽ duyệt trong thời gian sớm nhất',
        ]);
    }
}
