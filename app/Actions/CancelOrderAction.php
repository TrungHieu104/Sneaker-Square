<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use App\Services\OrderStock;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

/**
 * Cancels an unpaid order and puts everything it reserved back.
 *
 * Two defects lived in the code this replaces. It restored stock by walking
 * the session cart, but the cart had already been forgotten before the
 * customer was sent to the gateway — so the loop ran zero times and the stock
 * stayed reserved forever. And it then called forceDelete() on the order with
 * no check that the caller owned it, or that the order was even unpaid.
 *
 * Stock is now restored from `order_details`, which is what the order actually
 * took, using the same atomic increment style as PlaceOrderAction. The order
 * is marked cancelled rather than deleted, so the record survives for the
 * admin's order list and the revenue reports.
 */
class CancelOrderAction
{
    public function __construct(private OrderStock $stock, private WalletService $wallets) {}

    /**
     * @param  bool  $allowPaid  true when a person is cancelling an order they
     *                           already paid for, which is refunded to their
     *                           wallet here. A gateway callback must never do this.
     * @return bool whether this call is the one that cancelled the order
     */
    public function execute(OrderModel $order, bool $allowPaid = false, string $actor = OrderStatusLogModel::ACTOR_SYSTEM, ?string $note = null): bool
    {
        return DB::transaction(function () use ($order, $allowPaid, $actor, $note) {
            $fresh = OrderModel::where('order_id', $order->order_id)
                ->lockForUpdate()
                ->first();

            if (! $fresh) {
                return false;
            }

            // A failed payment must not be able to void an order that was paid
            // for — that would be a free way to wipe somebody else's purchase.
            if (! $allowPaid && (int) $fresh->order_payment_status === 1) {
                return false;
            }

            // Safe to run twice: the gateway may send both a redirect and an IPN.
            if ($fresh->hasStatus(OrderStatus::Cancelled)) {
                return false;
            }

            $this->stock->release($fresh);

            // Money already taken goes back to the customer's wallet on the
            // spot: the shop is not going to deliver anything for it, and
            // there is nothing left for an admin to decide.
            if ((int) $fresh->order_payment_status === 1) {
                $this->wallets->refundOrder($fresh, (int) $fresh->order_total, 'Hoàn tiền huỷ đơn '.$fresh->order_code);
                $fresh->order_refund_required = false;
            }

            return $fresh->moveTo(OrderStatus::Cancelled, $actor, $note);
        });
    }
}
