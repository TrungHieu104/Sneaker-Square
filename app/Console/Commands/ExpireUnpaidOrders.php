<?php

namespace App\Console\Commands;

use App\Services\Payment\OrderPayments;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid';

    protected $description = 'Huỷ các đơn thanh toán qua cổng đã quá hạn mà chưa trả tiền, trả hàng về kho';

    public function handle(OrderPayments $payments): int
    {
        $count = $payments->expireOverdue();

        $this->info('Đã huỷ '.$count.' đơn quá hạn thanh toán.');

        return self::SUCCESS;
    }
}
