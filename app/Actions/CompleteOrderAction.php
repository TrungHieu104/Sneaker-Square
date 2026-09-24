<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use App\Services\OrderRevenue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Closes an order the customer has received, and books its revenue.
 *
 * The customer's button and the scheduled auto-complete both land here, and can
 * land at the same moment; the row lock and the status check make whichever
 * arrives second a no-op.
 */
class CompleteOrderAction
{
    public function __construct(private OrderRevenue $revenue) {}

    /**
     * @return bool whether this call is the one that completed the order
     */
    public function execute(OrderModel $order, string $actor = OrderStatusLogModel::ACTOR_CUSTOMER, ?string $note = null): bool
    {
        return DB::transaction(function () use ($order, $actor, $note) {
            $fresh = OrderModel::where('order_id', $order->order_id)->lockForUpdate()->first();

            if (! $fresh || ! $fresh->isAwaitingReceipt()) {
                return false;
            }

            $fresh->order_completed_at = Carbon::now();
            $fresh->moveTo(OrderStatus::Completed, $actor, $note);

            $this->revenue->record($fresh);

            return true;
        });
    }
}
