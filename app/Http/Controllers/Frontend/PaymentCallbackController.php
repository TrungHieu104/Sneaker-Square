<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\ConfirmPaymentAction;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderModel;
use App\Models\PaymentAttemptModel;
use App\Models\WalletTopupModel;
use App\Services\OrderMailer;
use App\Services\Payment\InvalidPaymentCallbackException;
use App\Services\Payment\OrderPayments;
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
        private readonly OrderPayments $payments,
        private readonly OrderMailer $mailer,
        private readonly WalletTopups $topups,
    ) {}

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

        $order = $this->payments->orderForCode($callback->orderCode);

        if ($callback->outcome === PaymentOutcome::Failed || $callback->outcome === PaymentOutcome::Cancelled) {
            return $this->backToOrder($order);
        }

        // The gateway saying "paid" is not the same as this payment having
        // paid for the order: it may have arrived after the order was paid
        // another way, or cancelled for running out of time. Either way the
        // money went to the wallet, and the success page would be a lie.
        $attempt = PaymentAttemptModel::where('code', $callback->orderCode)->first();
        $handedBack = $attempt?->status === PaymentAttemptModel::REFUNDED
            || ($order && ! $attempt && ($order->hasStatus(OrderStatus::Cancelled) || $order->collectsAtDoor()));

        if ($order && $handedBack) {
            Session::flash('iconMessage', 'warning');

            return redirect()->route('orderBill.checkout', $order->order_code)->with(
                'message',
                match (true) {
                    $order->hasStatus(OrderStatus::Cancelled) => 'Đơn hàng đã bị huỷ trước khi tiền về. Khoản vừa thanh toán đã được hoàn vào SPay của bạn.',
                    $order->collectsAtDoor() => 'Đơn hàng đã được giao cho đơn vị vận chuyển để thu tiền khi nhận hàng. Khoản vừa thanh toán đã được hoàn vào SPay của bạn.',
                    default => 'Đơn hàng đã được thanh toán trước đó. Khoản vừa thanh toán đã được hoàn vào SPay của bạn.',
                },
            );
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
                // Not a reason to cancel: the customer backed out of one
                // attempt and may well pay on the next, or another way. The
                // order lapses on its own when its window closes.
                $this->payments->markFailed($callback->orderCode);
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

    /**
     * A payment that did not go through lands the customer on their order,
     * where they can try again or pay another way, rather than on a dead end.
     */
    private function backToOrder(?OrderModel $order): RedirectResponse
    {
        if (! $order || ! $this->payments->canChangeMethod($order)) {
            return redirect()->route('failed.checkout');
        }

        $deadline = $this->payments->deadlineFor($order);

        Session::flash('iconMessage', 'warning');

        return redirect()->route('orderBill.checkout', $order->order_code)->with(
            'message',
            'Thanh toán chưa hoàn tất. Bạn có thể thanh toán lại hoặc đổi phương thức'
                .($deadline ? ' trước '.$deadline->format('H:i d/m/Y') : '').'.',
        );
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
