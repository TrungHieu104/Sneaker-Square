<?php

namespace App\Console\Commands;

use App\Services\CartReminders\AbandonedCarts;
use App\Services\CartReminders\CartReminderSender;
use Illuminate\Console\Command;

class RemindAbandonedCarts extends Command
{
    protected $signature = 'carts:remind';

    protected $description = 'Gửi email nhắc các giỏ hàng bị bỏ quên (theo cấu hình Nhắc giỏ hàng)';

    public function handle(AbandonedCarts $carts, CartReminderSender $sender): int
    {
        $sent = 0;

        foreach ($carts->due(now()) as $due) {
            $sent += $sender->send($due['cart'], $due['step']) === null ? 0 : 1;
        }

        $this->info("Đã xếp hàng {$sent} email nhắc giỏ hàng.");

        return self::SUCCESS;
    }
}
