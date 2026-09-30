<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\OrderModel;
use App\Services\OrderMailer;
use App\Services\Payment\OrderPayments;
use App\Services\Payment\PaymentNotAllowed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

/**
 * A customer paying for an order they have already placed: again, after
 * backing out of the gateway, or some other way altogether.
 */
class OrderPaymentController extends Controller
{
    public function __construct(
        private readonly OrderPayments $payments,
        private readonly OrderMailer $mailer,
    ) {}

    public function pay(string $order_code): RedirectResponse
    {
        $order = $this->ownOrder($order_code);

        try {
            return redirect()->away($this->payments->startAttempt($order));
        } catch (PaymentNotAllowed $e) {
            return $this->refuse($order, $e);
        }
    }

    public function change(Request $request, string $order_code): RedirectResponse
    {
        $data = $request->validate([
            'payment' => ['required', Rule::in(OrderPayments::METHODS)],
        ], [
            'payment.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment.in' => 'Phương thức thanh toán không hợp lệ.',
        ]);

        $order = $this->ownOrder($order_code);

        try {
            $next = $this->payments->changeMethod($order, $data['payment']);
        } catch (PaymentNotAllowed $e) {
            return $this->refuse($order, $e);
        }

        if ($next !== null) {
            return redirect()->away($next);
        }

        // Cash on delivery and the wallet settle the order here and now, which
        // is when checkout would have sent the confirmation for them.
        $this->mailer->sendConfirmation($order->fresh());

        Session::flash('iconMessage', 'success');

        return redirect()->route('orderBill.checkout', $order->order_code)
            ->with('message', 'Đã đổi phương thức thanh toán thành '.$order->fresh()->paymentLabel().'.');
    }

    private function ownOrder(string $orderCode): OrderModel
    {
        return OrderModel::where('order_code', $orderCode)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    private function refuse(OrderModel $order, PaymentNotAllowed $e): RedirectResponse
    {
        Session::flash('iconMessage', 'error');

        return redirect()->route('orderBill.checkout', $order->order_code)->with('message', $e->getMessage());
    }
}
