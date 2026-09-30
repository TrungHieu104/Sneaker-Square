<?php

namespace App\Services\Payment;

use App\Actions\CancelOrderAction;
use App\Actions\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use App\Models\PaymentAttemptModel;
use App\Models\WalletTransactionModel;
use App\Services\ShopSettings;
use App\Services\Wallet\InsufficientBalance;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Everything a customer can do with an order they have not paid for yet:
 * pay it again, pay it some other way, or leave it until it lapses.
 *
 * An unpaid gateway order is reserved stock with a clock on it. Backing out
 * of the gateway's page does not cancel it — the customer usually wants to
 * try again, or pay differently — but it cannot hold the goods forever, so
 * it is cancelled once the payment window closes.
 */
class OrderPayments
{
    /**
     * The methods that send the customer away to pay.
     */
    public const GATEWAYS = ['redirect', 'payUrl'];

    /**
     * Every method a customer may switch an unpaid order to.
     */
    public const METHODS = [
        OrderModel::PAY_ON_DELIVERY,
        PlaceOrderAction::PAY_FROM_WALLET,
        'redirect',
        'payUrl',
    ];

    public function __construct(
        private PaymentGatewayManager $gateways,
        private WalletService $wallets,
        private CancelOrderAction $cancelOrder,
    ) {}

    public static function windowMinutes(): int
    {
        return app(ShopSettings::class)->paymentWindowMinutes();
    }

    /**
     * When an unpaid gateway order will be cancelled, or null for an order
     * that is not waiting on a gateway at all.
     *
     * Stored on the order rather than worked out from when it was placed: a
     * cash order switched to a gateway an hour later would otherwise be past
     * its deadline the moment it got one. Orders placed before the column
     * existed fall back to their creation time.
     */
    public function deadlineFor(OrderModel $order): ?Carbon
    {
        if (! $this->awaitsGateway($order)) {
            return null;
        }

        if ($order->order_payment_due_at) {
            return $order->order_payment_due_at->copy();
        }

        return $order->created_at?->copy()->addMinutes(self::windowMinutes());
    }

    public static function newDeadline(): Carbon
    {
        return Carbon::now()->addMinutes(self::windowMinutes());
    }

    private function awaitsGateway(OrderModel $order): bool
    {
        return in_array($order->order_payment, self::GATEWAYS, true)
            && (int) $order->order_payment_status === 0
            && $order->hasStatus(OrderStatus::New);
    }

    /**
     * Whether the customer may send the order to its gateway again.
     */
    public function canPay(OrderModel $order): bool
    {
        $deadline = $this->deadlineFor($order);

        return $deadline !== null && $deadline->isFuture();
    }

    /**
     * Whether the order may still be paid some other way.
     *
     * Only while the shop has not taken it on: once confirmed, a cash order is
     * booked with the courier for that cash, and changing it from here would
     * leave the shipper collecting money that was paid already.
     */
    public function canChangeMethod(OrderModel $order): bool
    {
        if ((int) $order->order_payment_status === 1 || ! $order->hasStatus(OrderStatus::New)) {
            return false;
        }

        $deadline = $this->deadlineFor($order);

        return $deadline === null || $deadline->isFuture();
    }

    /**
     * Opens a fresh attempt on the order's gateway and returns where to send
     * the customer.
     *
     * @throws PaymentNotAllowed
     */
    public function startAttempt(OrderModel $order): string
    {
        $attempt = DB::transaction(function () use ($order) {
            $fresh = OrderModel::where('order_id', $order->order_id)->lockForUpdate()->firstOrFail();

            if (! $this->canPay($fresh)) {
                throw new PaymentNotAllowed('Đơn hàng này không còn chờ thanh toán.');
            }

            $attempt = PaymentAttemptModel::create([
                'order_id' => $fresh->order_id,
                // Filled in below: the code carries the attempt's own id.
                'code' => 'tmp-'.uniqid('', true),
                'gateway' => (string) $fresh->order_payment,
                'amount' => (int) $fresh->order_total,
                'status' => PaymentAttemptModel::PENDING,
            ]);

            $attempt->code = PaymentAttemptModel::codeFor($fresh, (int) $attempt->attempt_id);
            $attempt->save();

            return $attempt;
        });

        $gateway = $this->gateways->byName($attempt->gateway);

        try {
            if (! $gateway) {
                throw new InvalidPaymentCallbackException('Phương thức thanh toán không hợp lệ.');
            }

            return $gateway->checkoutUrl(GatewayCharge::forAttempt($attempt, $order, $this->deadlineFor($order)));
        } catch (Throwable $e) {
            report($e);
            $attempt->forceFill(['status' => PaymentAttemptModel::FAILED])->save();

            throw new PaymentNotAllowed('Không kết nối được cổng thanh toán. Bạn thử lại hoặc chọn cách thanh toán khác nhé.');
        }
    }

    /**
     * Moves an unpaid order to another way of paying.
     *
     * @return ?string where to send the customer next, when the new method is
     *                 a gateway; null when the order is settled on the spot
     *
     * @throws PaymentNotAllowed
     */
    public function changeMethod(OrderModel $order, string $method): ?string
    {
        if (! in_array($method, self::METHODS, true)) {
            throw new PaymentNotAllowed('Phương thức thanh toán không hợp lệ.');
        }

        DB::transaction(function () use ($order, $method) {
            $fresh = OrderModel::where('order_id', $order->order_id)->lockForUpdate()->firstOrFail();

            if (! $this->canChangeMethod($fresh)) {
                throw new PaymentNotAllowed('Đơn hàng này không còn đổi được phương thức thanh toán.');
            }

            $wasGateway = in_array($fresh->order_payment, self::GATEWAYS, true);
            $fresh->order_payment = $method;

            if ($method === PlaceOrderAction::PAY_FROM_WALLET) {
                $this->payFromWallet($fresh);
            }

            // Moving from one gateway to another keeps the clock running, or
            // switching back and forth would hold the stock indefinitely.
            // Arriving at a gateway from cash starts it; leaving for cash or
            // the wallet stops it.
            if (! in_array($method, self::GATEWAYS, true)) {
                $fresh->order_payment_due_at = null;
            } elseif (! $wasGateway) {
                $fresh->order_payment_due_at = self::newDeadline();
            }

            $fresh->save();
            $order->setRawAttributes($fresh->getAttributes(), true);
        });

        return in_array($method, self::GATEWAYS, true) ? $this->startAttempt($order) : null;
    }

    /**
     * Cancels every gateway order whose payment window has closed, returning
     * its stock and coupon.
     *
     * @return int how many orders were cancelled
     */
    public function expireOverdue(): int
    {
        $now = Carbon::now();
        $legacyCutoff = $now->copy()->subMinutes(self::windowMinutes());
        $expired = 0;

        OrderModel::query()
            ->whereIn('order_payment', self::GATEWAYS)
            ->where('order_payment_status', 0)
            ->where('order_status', OrderStatus::New)
            ->where(function ($q) use ($now, $legacyCutoff) {
                $q->where('order_payment_due_at', '<=', $now)
                    ->orWhere(fn ($old) => $old->whereNull('order_payment_due_at')->where('created_at', '<=', $legacyCutoff));
            })
            ->each(function (OrderModel $order) use (&$expired) {
                $cancelled = $this->cancelOrder->execute(
                    $order,
                    false,
                    OrderStatusLogModel::ACTOR_SYSTEM,
                    'Quá hạn thanh toán',
                );

                $expired += $cancelled ? 1 : 0;
            });

        return $expired;
    }

    /**
     * A gateway reported that this attempt did not go through. The order is
     * left as it was: the customer may try again until the window closes.
     */
    public function markFailed(string $code): ?OrderModel
    {
        $attempt = PaymentAttemptModel::where('code', $code)->first();

        if (! $attempt) {
            return OrderModel::where('order_code', $code)->first();
        }

        if ($attempt->status === PaymentAttemptModel::PENDING) {
            $attempt->forceFill(['status' => PaymentAttemptModel::FAILED])->save();
        }

        return $attempt->order;
    }

    /**
     * The order a gateway code belongs to, whether it is an attempt's code or,
     * for orders placed before attempts existed, the order's own.
     */
    public function orderForCode(string $code): ?OrderModel
    {
        $attempt = PaymentAttemptModel::where('code', $code)->first();

        return $attempt ? $attempt->order : OrderModel::where('order_code', $code)->first();
    }

    /**
     * @throws PaymentNotAllowed
     */
    private function payFromWallet(OrderModel $order): void
    {
        try {
            $this->wallets->debit(
                $this->wallets->for((int) $order->user_id),
                (int) $order->order_total,
                WalletTransactionModel::TYPE_PAYMENT,
                'Thanh toán đơn hàng '.$order->order_code,
                WalletService::REF_ORDER,
                (int) $order->order_id,
            );
        } catch (InsufficientBalance) {
            throw new PaymentNotAllowed('Số dư SPay không đủ để thanh toán đơn này.');
        }

        $order->order_payment_status = 1;
        $order->order_payment_time = Carbon::now('Asia/Ho_Chi_Minh');
    }
}
