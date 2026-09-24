<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\CancelOrderAction;
use App\Actions\ConfirmPaymentAction;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderModel;
use App\Models\WalletTopupModel;
use App\Services\OrderMailer;
use App\Services\Payment\InvalidPaymentCallbackException;
use App\Services\Payment\PaymentCallback;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentOutcome;
use App\Services\Wallet\WalletTopups;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Everything a payment gateway sends back after the customer leaves us.
 *
 * This used to be ProductController::processCheckout(), which read $_GET
 * directly and believed it. The order code alone was enough to mark any order
 * paid, and a made-up failure code was enough to hard-delete one. Nothing here
 * acts on a callback until the gateway has verified its own signature.
 */
class PaymentCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly ConfirmPaymentAction $confirmPayment,
        private readonly CancelOrderAction $cancelOrder,
        private readonly OrderMailer $mailer,
        private readonly WalletTopups $topups,
    ) {
    }

    /**
     * The customer's browser coming back from the gateway.
     */
    public function handleReturn(Request $request): RedirectResponse
    {
        $params = $request->query();

        // Somebody opening the return URL out of curiosity is not an attack.
        if (! $this->gateways->looksLikeCallback($params)) {
            return redirect()->route('product.page');
        }

        try {
            $callback = $this->gateways->verify($params);
            $this->apply($callback);
        } catch (InvalidPaymentCallbackException $e) {
            $this->logRejection($request, $e);
            Session::flash('iconMessage', 'error');

            return redirect()->route('failed.checkout');
        }

        // A top-up has no order behind it, so none of the order checks below
        // mean anything for one; the customer goes back to their wallet.
        if (WalletTopupModel::looksLikeTopupCode($callback->orderCode)) {
            return $this->backToWallet($callback);
        }

        if ($callback->outcome === PaymentOutcome::Failed || $callback->outcome === PaymentOutcome::Cancelled) {
            return redirect()->route('failed.checkout');
        }

        // The gateway saying "paid" is not the same as the order having gone
        // through: money can arrive for an order a failed callback already
        // cancelled, and showing that customer the success page tells them they
        // have an order they do not have.
        $order = OrderModel::where('order_code', $callback->orderCode)->first();

        if ($order && $order->hasStatus(OrderStatus::Cancelled)) {
            Session::flash('iconMessage', 'error');
            Session::flash(
                'message',
                'Đơn hàng đã bị hủy trước khi thanh toán được ghi nhận. Khoản tiền này sẽ được hoàn lại, vui lòng liên hệ cửa hàng.'
            );

            return redirect()->route('failed.checkout');
        }

        return redirect()->route('success.checkout');
    }

    /**
     * The gateway's own server telling us what happened.
     *
     * The browser redirect can be abandoned halfway — the customer closes the
     * tab and we never hear about the payment. This notification arrives
     * regardless, which is why both paths lead to the same idempotent actions.
     */
    public function handleIpn(Request $request): JsonResponse
    {
        $params = $request->all();

        try {
            $callback = $this->gateways->verify($params);
            $this->apply($callback);
        } catch (InvalidPaymentCallbackException $e) {
            $this->logRejection($request, $e);

            return response()->json(['resultCode' => 1, 'message' => $e->getMessage()], 400);
        }

        return response()->json(['resultCode' => 0, 'message' => 'received']);
    }

    /**
     * Applies a verified callback to the order it names.
     *
     * @throws InvalidPaymentCallbackException
     */
    private function apply(PaymentCallback $callback): void
    {
        if (WalletTopupModel::looksLikeTopupCode($callback->orderCode)) {
            $this->applyTopup($callback);

            return;
        }

        switch ($callback->outcome) {
            case PaymentOutcome::Paid:
                // Only the call that settled the payment gets an order back, so the
                // confirmation goes out once however often the gateway tells us.
                if ($order = $this->confirmPayment->execute($callback)) {
                    $this->mailer->sendConfirmation($order);
                }
                break;

            case PaymentOutcome::Cancelled:
            case PaymentOutcome::Failed:
                $order = OrderModel::where('order_code', $callback->orderCode)->first();

                if ($order) {
                    $this->cancelOrder->execute($order);
                }
                break;

            case PaymentOutcome::Pending:
                // Authorised but not captured yet. The order stays unpaid and
                // keeps its stock until a later notification settles it.
                break;
        }
    }

    /**
     * @throws InvalidPaymentCallbackException
     */
    private function applyTopup(PaymentCallback $callback): void
    {
        match ($callback->outcome) {
            PaymentOutcome::Paid => $this->topups->settle($callback),
            PaymentOutcome::Cancelled, PaymentOutcome::Failed => $this->topups->markFailed($callback->orderCode),
            // Authorised but not captured: the wallet waits for the settlement.
            PaymentOutcome::Pending => null,
        };
    }

    private function backToWallet(PaymentCallback $callback): RedirectResponse
    {
        $paid = $callback->outcome === PaymentOutcome::Paid;

        Session::flash('iconMessage', $paid ? 'success' : 'error');

        return redirect()->route('user.wallet')->with(
            'message',
            $paid ? 'Nạp tiền thành công.' : 'Nạp tiền không thành công.',
        );
    }

    private function logRejection(Request $request, InvalidPaymentCallbackException $e): void
    {
        Log::warning('Rejected a payment callback', [
            'reason' => $e->getMessage(),
            'ip' => $request->ip(),
            'params' => $request->except(['signature', 'vnp_SecureHash']),
        ]);
    }
}
