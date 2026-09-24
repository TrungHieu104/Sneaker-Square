<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use App\Services\OrderStock;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

/**
 * GHN has brought an undeliverable parcel back to the shop.
 *
 * The goods are on the shelf again, so their stock and the coupon use are
 * released the same way a cancellation releases them. No revenue is touched:
 * an order is only counted once the customer has it, and this one never got
 * that far.
 */
class ReceiveReturnedParcelAction
{
    public function __construct(private OrderStock $stock, private WalletService $wallets) {}

    /**
     * @return bool whether this call is the one that took the parcel back
     */
    public function execute(OrderModel $order): bool
    {
        return DB::transaction(function () use ($order) {
            $fresh = OrderModel::where('order_id', $order->order_id)->lockForUpdate()->first();

            // Only a parcel still on its way back can land. GHN resends
            // callbacks, and a second "returned" must not restock twice.
            if (! $fresh || ! $fresh->hasStatus(OrderStatus::Returning)) {
                return false;
            }

            $this->stock->release($fresh);

            if ((int) $fresh->order_payment_status === 1) {
                $this->wallets->refundOrder($fresh, (int) $fresh->order_total, 'Hoàn tiền đơn giao không thành công '.$fresh->order_code);
                $fresh->order_refund_required = false;
            }

            $fresh->order_delivery_status = 0;

            return $fresh->moveTo(OrderStatus::Returned, OrderStatusLogModel::ACTOR_CARRIER, 'GHN đã trả hàng về kho');
        });
    }
}
