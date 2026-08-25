<?php

namespace App\Actions;

use App\Models\OrderModel;
use App\Services\Payment\InvalidPaymentCallbackException;
use App\Services\Payment\PaymentCallback;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Marks an order paid, but only on the strength of a verified callback.
 *
 * The controller used to do this itself, from the query string alone:
 *
 *     if (isset($_GET['orderId']) || isset($_GET['vnp_TxnRef'])) {
 *         $order->order_payment_status = 1;
 *     }
 *
 * Anyone could open that URL with any order code and get the goods for free.
 * Three checks stand in the way now: the gateway has verified its signature
 * before a PaymentCallback can exist at all, the amount must equal the total
 * this server stored, and an order already paid is left exactly as it is.
 */
class ConfirmPaymentAction
{
    /**
     * @throws InvalidPaymentCallbackException when the order is unknown or the
     *                                         amount does not match
     */
    public function execute(PaymentCallback $callback): OrderModel
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

            // The gateway must be paying for what we actually charged. A callback
            // replayed from a cheaper order would otherwise settle an expensive one.
            if ($callback->amount !== null && $callback->amount !== (int) $order->order_total) {
                throw new InvalidPaymentCallbackException(
                    'Số tiền thanh toán không khớp với đơn hàng ' . $callback->orderCode . '.'
                );
            }

            // Both the browser redirect and the server-to-server IPN arrive for the
            // same payment, so this has to be safe to run twice.
            if ((int) $order->order_payment_status === 1) {
                return $order;
            }

            $order->order_payment_status = 1;
            $order->order_payment_time = Carbon::now('Asia/Ho_Chi_Minh');
            $order->save();

            return $order;
        });
    }
}
