<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('order')->insert([
            [
                'order_code' => 'A24173',
                'order_name' => 'Phạm Hoàng Thúy Nga',
                'order_email' => 'customer01@example.com',
                'order_address' => '391/375 Trần Hưng Đạo, Phường Cầu Kho, Quận 1, TP Hồ Chí Minh',
                'order_phone' => '09347935983',
                'order_total' => 83000000,
                'order_payment' => 1, 
                'order_payment_status' => 1,
                // 'order_dilivery_status' => 0,
                'order_date' => now(),
                'order_status' => 0,
                'coupon_id' => 2,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_code' => 'B00384',
                'order_name' => 'Nguyễn Long Tấn',
                'order_email' => 'customer02@example.com',
                'order_address' => '111 Khuông Việt, Phường Phú Trung, Quận Tân Phú, TP Hồ Chí Minh',
                'order_phone' => '0903047443',
                'order_total' => 18300000,
                'order_payment' => 1, 
                'order_payment_status' => 1,
                // 'order_dilivery_status' => 0,
                'order_date' => now(),
                'order_status' => 0,
                'coupon_id' => 5,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_code' => 'A00328',
                'order_name' => 'Nguyễn Văn Nam',
                'order_email' => 'customer03@example.com',
                'order_address' => '297/3 Tô Hiến Thành, Phường 13, Quận 10, TP Hồ Chí Minh',
                'order_phone' => '0983003421',
                'order_total' => 430000,
                'order_payment' => 1, 
                'order_payment_status' => 1,
                'order_date' => now(),
                'order_status' => 0,
                'coupon_id' => 2,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_code' => 'C48500',
                'order_name' => 'Nguyễn Thị Hương Tràm',
                'order_email' => 'customer04@example.com',
                'order_address' => '401 Phan Xích Long, Phường 3, Quận Phú Nhuận, TP Hồ Chí Minh.',
                'order_phone' => '098040222',
                'order_total' => 1890000,
                'order_payment' => 1, 
                'order_payment_status' => 1,
                'order_date' => now(),
                'order_status' => 0,
                'coupon_id' => null,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_code' => 'V09300',
                'order_name' => 'Hồ Thị Anh Trúc',
                'order_email' => 'customer05@example.com',
                'order_address' => '94 Lê Văn Thọ, Phường 11, Quận Gò Vấp, TP Hồ Chí Minh',
                'order_phone' => '033983777',
                'order_total' => 1230000,
                'order_payment' => 1, 
                'order_payment_status' => 1,
                'order_date' => now(),
                'order_status' => 0,
                'coupon_id' => 5,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'order_code' => 'O39000',
                'order_name' => 'Hà Thị Huyền Anh',
                'order_email' => 'customer06@example.com',
                'order_address' => '54 Tô Vĩnh Diện, Phường Linh Chiểu, Tp Thủ Đức, TP Hồ Chí Minh.',
                'order_phone' => '093485333',
                'order_total' => 9940000,
                'order_payment' => 1, 
                'order_payment_status' => 1,
                'order_date' => now(),
                'order_status' => 0,
                'coupon_id' => 8,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
