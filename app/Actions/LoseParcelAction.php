<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use App\Services\OrderStock;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

/**
 * GHN reports the parcel lost. `lost` is final on GHN's side: nothing more
 * will come for this parcel, so the order ends here.
 *
 * The customer gets back what they paid and their coupon use; the stock does
 * not come back, because the goods never returned to the shelf. What GHN pays
 * the shop in compensation is settled between the shop and GHN.
 */
class LoseParcelAction
{
    public function __construct(private OrderStock $stock, private WalletService $wallets) {}

    /**
     * @return bool whether this call is the one that closed the order
     */
    public function execute(OrderModel $order): bool
    {
        return DB::transaction(function () use ($order) {
            $fresh = OrderModel::where('order_id', $order->order_id)->lockForUpdate()->first();

            // GHN resends callbacks; only an order still out with GHN is closed.
            if (! $fresh || ! $fresh->hasStatus(OrderStatus::ReadyToShip, OrderStatus::Delivering, OrderStatus::Returning)) {
                return false;
            }

            $this->stock->releaseCouponOf($fresh);

            if ((int) $fresh->order_payment_status === 1) {
                $this->wallets->refundOrder($fresh, (int) $fresh->order_total, 'Hoàn tiền đơn '.$fresh->order_code.' bị GHN làm thất lạc');
                $fresh->order_refund_required = false;
            }

            $fresh->order_delivery_status = 0;
            $fresh->order_cancel_reason = 'Đơn vị vận chuyển làm thất lạc hàng.';

            return $fresh->moveTo(OrderStatus::Cancelled, OrderStatusLogModel::ACTOR_CARRIER, 'GHN báo hàng bị thất lạc');
        });
    }
}
