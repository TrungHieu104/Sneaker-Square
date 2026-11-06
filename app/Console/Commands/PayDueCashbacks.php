<?php

namespace App\Console\Commands;

use App\Actions\PayOrderCashbackAction;
use App\Enums\OrderStatus;
use App\Logging\LogChannel;
use App\Models\OrderCashbackModel;
use App\Services\ShopSettings;
use App\Services\Wallet\WalletTampered;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PayDueCashbacks extends Command
{
    protected $signature = 'orders:pay-cashback';

    protected $description = 'Cộng tiền hoàn vào ví cho các đơn đã hết hạn trả hàng';

    public function handle(ShopSettings $settings, PayOrderCashbackAction $pay): int
    {
        $cutoff = now()->subDays($settings->returnDays());
        $paid = 0;

        OrderCashbackModel::whereNull('paid_at')
            ->whereHas('order', fn ($orders) => $orders
                ->where('order_status', OrderStatus::Completed)
                ->where('order_completed_at', '<=', $cutoff))
            ->chunkById(100, function ($cashbacks) use ($pay, &$paid) {
                foreach ($cashbacks as $cashback) {
                    try {
                        $paid += $pay->execute($cashback) ? 1 : 0;
                    } catch (WalletTampered) {
                        // Already raised as an alarm; one held wallet must not
                        // stop everyone else being paid.
                        Log::channel(LogChannel::PAYMENT)->warning('Cashback skipped: wallet is held', ['order_id' => $cashback->order_id]);
                    }
                }
            }, 'order_id');

        $this->info("Đã hoàn tiền cho {$paid} đơn.");

        return self::SUCCESS;
    }
}
