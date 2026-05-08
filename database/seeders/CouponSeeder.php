<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $coupon_name = "Mã giảm $i";
            $coupon_code = "CODE$i";
            $coupon_condition = rand(0, 1);
            $couponValue = $coupon_condition === 1 ? round(rand(10000, 200000), -3) : rand(10, 50);; 
            $coupon_quantity = rand(1, 10);
            $coupon_used = 0;
            $coupon_start = now()->addDays(rand(1, 30));
            $coupon_end = $coupon_start->copy()->addDays(rand(1, 30));

            DB::table('coupon')->insert([
                'coupon_name' => $coupon_name,
                'coupon_code' => $coupon_code,
                'coupon_value' => $couponValue,
                'coupon_quantity' => $coupon_quantity,
                'coupon_used' => $coupon_used,
                'coupon_condition' => $coupon_condition,
                'coupon_start' => $coupon_start,
                'coupon_end' => $coupon_end,
                'created_at' => Now(), 
                'updated_at' => Now()
            ]);
        }
    }
}
