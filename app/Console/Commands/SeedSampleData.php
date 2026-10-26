<?php

namespace App\Console\Commands;

use App\Services\SampleData;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedSampleData extends Command
{
    protected $signature = 'sample:seed';

    protected $description = 'Nạp dữ liệu hành vi mẫu (lượt xem, giỏ hàng, đơn, email nhắc), thay bản mẫu cũ nếu có (chỉ chạy ở local)';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Lệnh này chỉ chạy được ở môi trường local. APP_ENV hiện tại: '.app()->environment());

            return self::FAILURE;
        }

        $this->callSilent('db:seed', ['--class' => SampleDataSeeder::class, '--force' => true]);

        $this->table(['Dữ liệu mẫu', 'Số dòng'], [
            ['Phiên hành vi', DB::table('user_events')->where('session_id', 'like', SampleData::SESSION_PREFIX.'%')->distinct()->count('session_id')],
            ['Sự kiện', DB::table('user_events')->where('session_id', 'like', SampleData::SESSION_PREFIX.'%')->count()],
            ['Tài khoản khách mẫu', DB::table('users')->where('email', 'like', '%'.SampleData::EMAIL_DOMAIN)->count()],
            ['Email nhắc giỏ hàng', DB::table('cart_reminders')
                ->whereIn('user_id', DB::table('users')->where('email', 'like', '%'.SampleData::EMAIL_DOMAIN)->select('user_id'))
                ->count()],
        ]);
        $this->line('Đăng nhập thử bằng khachmau01'.SampleData::EMAIL_DOMAIN.' / '.SampleDataSeeder::PASSWORD.'. Xoá bằng: php artisan sample:clear');

        return self::SUCCESS;
    }
}
