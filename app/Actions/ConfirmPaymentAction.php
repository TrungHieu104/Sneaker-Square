<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Services\Payment\InvalidPaymentCallbackException;
use App\Services\Payment\PaymentCallback;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Marks an order paid, but only on the strength of a verified callback.
 *
 * Three things stand between a callback and a paid order: the gateway has
 * verified its signature before a PaymentCallback can exist at all, the amount
 * must equal the total this server stored, and an order already paid is left
 * exactly as it is.
 */
class ConfirmPaymentAction
{
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
            $order = OrderModel::where('order_code', $callback->orderCode)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new InvalidPaymentCallbackException(
                    'Không tìm thấy đơn hàng ' . $callback->orderCode . '.'
                );
            }

            if ($callback->amount !== null && $callback->amount !== (int) $order->order_total) {
                throw new InvalidPaymentCallbackException(
                    'Số tiền thanh toán không khớp với đơn hàng ' . $callback->orderCode . '.'
                );
            }

            if ((int) $order->order_payment_status === 1) {
                return null;
            }

            if ((int) $order->order_status === OrderStatus::Cancelled->value) {
                $this->recordPayment($order);

                Log::warning('Payment arrived for an order that was already cancelled', [
                    'order_code' => $order->order_code,
                    'amount' => $callback->amount,
                ]);

                return null;
            }

            $this->recordPayment($order);

            return $order;
        });
    }

    private function recordPayment(OrderModel $order): void
    {
        $order->order_payment_status = 1;
        $order->order_payment_time = Carbon::now('Asia/Ho_Chi_Minh');
        $order->save();
    }
}
