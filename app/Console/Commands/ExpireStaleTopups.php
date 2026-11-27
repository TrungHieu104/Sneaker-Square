<?php

namespace App\Console\Commands;

use App\Services\Wallet\WalletTopups;
use Illuminate\Console\Command;

class ExpireStaleTopups extends Command
{
    protected $signature = 'wallet:expire-topups';

    protected $description = 'Đóng các yêu cầu nạp SPay đã quá hạn mà khách chưa trả tiền';

    public function handle(WalletTopups $topups): int
    {
        $this->info('Đã đóng '.$topups->expireStale().' yêu cầu nạp quá hạn.');

        return self::SUCCESS;
    }
}
