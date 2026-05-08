<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->insert([
            [
                'name' => 'Quản trị Menu',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Slide',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Mã giảm giá',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị FAQ',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Thông tin',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Đơn hàng',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Thanh toán',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Giới thiệu',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Khách hàng liên hệ',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Thống kê truy cập',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Tài khoản (Khách hàng)',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Tài khoản (Quản trị viên)',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Sản phẩm (Thống kê)',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Sản phẩm',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Sản phẩm (Kho)',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Sản phẩm (Bình luận)',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Quản trị Bài viết',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'name' => 'Thống kê doanh thu',
                'guard_name' => 'web',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
        ]);
    }
}
