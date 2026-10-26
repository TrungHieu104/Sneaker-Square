<?php

namespace App\Console\Commands;

use App\Services\SampleData;
use Illuminate\Console\Command;

class ClearSampleData extends Command
{
    protected $signature = 'sample:clear {--force : Bỏ qua bước xác nhận}';

    protected $description = 'Xoá toàn bộ dữ liệu hành vi mẫu và tài khoản khách mẫu (chỉ chạy ở local)';

    public function handle(SampleData $sample): int
    {
        if (! app()->environment('local')) {
            $this->error('Lệnh này chỉ chạy được ở môi trường local. APP_ENV hiện tại: '.app()->environment());

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Xoá toàn bộ dữ liệu mẫu?', false)) {
            $this->line('Đã huỷ, không đụng gì vào dữ liệu.');

            return self::SUCCESS;
        }

        $removed = $sample->clear();

        $this->table(['Đã xoá', 'Số dòng'], [
            ['Sự kiện hành vi', $removed['events']],
            ['Email nhắc giỏ hàng', $removed['reminders']],
            ['Tài khoản khách mẫu', $removed['users']],
        ]);

        if ($removed['kept_users'] > 0) {
            $this->warn("Giữ lại {$removed['kept_users']} tài khoản mẫu vì đã có đơn hàng thật đặt bằng tài khoản đó.");
        }

        return self::SUCCESS;
    }
}
