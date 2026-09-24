<?php

namespace App\Console\Commands;

use App\Actions\CompleteOrderAction;
use App\Enums\OrderStatus;
use App\Models\OrderModel;
use App\Models\OrderStatusLogModel;
use App\Services\ShopSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Completes orders whose customer never pressed "Đã nhận được hàng".
 *
 * Only parcels GHN has marked delivered are eligible: that stamp is the one
 * reliable date the waiting period can run from. The period is read on every
 * run, so a change in the admin applies to orders already waiting.
 */
class AutoCompleteOrders extends Command
{
    protected $signature = 'orders:auto-complete';

    protected $description = 'Hoàn thành các đơn đã giao quá số ngày cấu hình mà khách chưa xác nhận';

    public function handle(ShopSettings $settings, CompleteOrderAction $complete): int
    {
        $days = $settings->autoCompleteDays();
        $cutoff = Carbon::now()->subDays($days);
        $completed = 0;

        OrderModel::where('order_status', OrderStatus::Delivered)
            ->whereNotNull('order_delivered_at')
            ->where('order_delivered_at', '<=', $cutoff)
            ->chunkById(100, function ($orders) use ($complete, &$completed) {
                foreach ($orders as $order) {
                    $completed += $complete->execute(
                        $order,
                        OrderStatusLogModel::ACTOR_SYSTEM,
                        'Quá hạn khách xác nhận, lệnh orders:auto-complete',
                    ) ? 1 : 0;
                }
            }, 'order_id');

        $this->info("Đã hoàn thành {$completed} đơn (giao quá {$days} ngày).");

        return self::SUCCESS;
    }
}
