<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\OrderCashbackModel;
use App\Models\OrderModel;
use App\Models\OrderReturnModel;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

/**
 * Pays an order's cashback once it can no longer be sent back.
 *
 * Never sooner: a return can only be asked for after an order completes, and
 * the wallet can be withdrawn to a bank, so money paid at completion could not
 * be taken back from a customer who then returned the goods. An order returned
 * in whole or in part leaves the Completed status and is never paid.
 */
class PayOrderCashbackAction
{
    public function __construct(private readonly WalletService $wallets) {}

    /**
     * @return bool whether this call is the one that paid it
     */
    public function execute(OrderCashbackModel $cashback): bool
    {
        return DB::transaction(function () use ($cashback) {
            $order = OrderModel::where('order_id', $cashback->order_id)->lockForUpdate()->first();

            if ($order === null || ! $this->isDue($order)) {
                return false;
            }

            $paid = $this->wallets->payCashback($order, (int) $cashback->amount, 'Đơn '.$order->order_code);
            $cashback->paid_at = now();
            $cashback->save();

            return $paid;
        });
    }

    private function isDue(OrderModel $order): bool
    {
        return $order->hasStatus(OrderStatus::Completed)
            && ! $order->canRequestReturn()
            && ! $order->orderReturns()->whereIn('status', [...OrderReturnModel::OPEN, ...OrderReturnModel::SETTLED])->exists();
    }
}
