<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\PaymentAttemptModel;
use App\Services\Payment\InvalidPaymentCallbackException;
use App\Services\Payment\PaymentCallback;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Marks an order paid, but only on the strength of a verified callback.
 *
 * Three things stand between a callback and a paid order: the gateway has
 * verified its signature before a PaymentCallback can exist at all, the amount
 * must equal what this server asked the gateway to collect, and an order is
 * paid at most once.
 *
 * Money that arrives and cannot settle anything — the order was paid by an
 * earlier attempt or from the wallet, was cancelled while the customer sat on
 * the gateway's page, or has gone out with cash to collect at the door — goes
 * straight back to the customer's wallet. Leaving
 * it recorded against a dead order means somebody has to notice it by hand.
 */
class ConfirmPaymentAction
{
    public function __construct(private WalletService $wallets) {}

    /**
     * @return ?OrderModel the order when this call is the one that settled it,
     *                     null when there was nothing left to settle
     *
     * @throws InvalidPaymentCallbackException when the order is unknown or the
     *                                         amount does not match
     */
    public function execute(PaymentCallback $callback): ?OrderModel
    {
        return DB::transaction(function () use ($callback) {
            $attempt = PaymentAttemptModel::where('code', $callback->orderCode)
                ->lockForUpdate()
                ->first();

            // Orders placed before attempts existed sent their own code to the
            // gateway, and a link opened then can still be paid now.
            $order = OrderModel::query()
                ->when(
                    $attempt,
                    fn ($q) => $q->where('order_id', $attempt->order_id),
                    fn ($q) => $q->where('order_code', $callback->orderCode),
                )
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new InvalidPaymentCallbackException(
                    'Không tìm thấy đơn hàng '.$callback->orderCode.'.'
                );
            }

            $expected = $attempt ? (int) $attempt->amount : (int) $order->order_total;

            if ($callback->amount !== null && $callback->amount !== $expected) {
                throw new InvalidPaymentCallbackException(
                    'Số tiền thanh toán không khớp với đơn hàng '.$order->order_code.'.'
                );
            }

            // The gateway repeating itself: the redirect and the IPN both say
            // "paid" for the one transaction.
            if ($attempt && in_array($attempt->status, [PaymentAttemptModel::PAID, PaymentAttemptModel::REFUNDED], true)) {
                return null;
            }

            $unusable = (int) $order->order_payment_status === 1
                || $order->hasStatus(OrderStatus::Cancelled)
                || $order->collectsAtDoor();

            if ($attempt) {
                $attempt->gateway_reference = $callback->reference;
                $attempt->settled_at = Carbon::now();
            }

            if ($unusable) {
                $this->handBack($order, $attempt, $callback);

                return null;
            }

            $attempt?->forceFill(['status' => PaymentAttemptModel::PAID])->save();
            $this->recordPayment($order, $callback->gateway);

            return $order;
        });
    }

    private function handBack(OrderModel $order, ?PaymentAttemptModel $attempt, PaymentCallback $callback): void
    {
        Log::warning('Payment arrived for an order that could not use it', [
            'order_code' => $order->order_code,
            'attempt' => $attempt?->code,
            'amount' => $callback->amount,
            'order_paid' => (int) $order->order_payment_status,
            'order_status' => $order->order_status->value,
        ]);

        if ($attempt) {
            $attempt->status = PaymentAttemptModel::REFUNDED;
            $attempt->save();

            $this->wallets->refundAttempt(
                $attempt,
                (int) $order->user_id,
                'Hoàn tiền thanh toán thừa cho đơn '.$order->order_code,
            );

            return;
        }

        // Without an attempt there is no telling a second payment from the
        // same one told twice, so an order already paid is left alone.
        if ((int) $order->order_payment_status === 1) {
            return;
        }

        // The order stays unpaid: the cash collected at the door is what
        // pays for it, and this money goes back.
        if ($order->collectsAtDoor()) {
            $this->wallets->refundOrder(
                $order,
                (int) $order->order_total,
                'Hoàn tiền thanh toán trực tuyến cho đơn '.$order->order_code.' đã chuyển sang thu tiền khi giao',
            );

            return;
        }

        $this->recordPayment($order, $callback->gateway);
        $this->wallets->refundOrder(
            $order,
            (int) $order->order_total,
            'Hoàn tiền đơn '.$order->order_code.' đã huỷ trước khi thanh toán về',
        );
    }

    /**
     * The method is rewritten to the gateway that actually took the money:
     * a customer who switched to cash on delivery and then paid on a tab they
     * had left open must not be asked for the money again at the door.
     */
    private function recordPayment(OrderModel $order, string $gateway): void
    {
        $order->order_payment = $gateway;
        $order->order_payment_status = 1;
        $order->order_payment_time = Carbon::now('Asia/Ho_Chi_Minh');
        $order->save();
    }
}
