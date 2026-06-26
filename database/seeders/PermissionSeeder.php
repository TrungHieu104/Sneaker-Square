<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'Quản trị Menu',
            'Quản trị Slide',
            'Quản trị Mã giảm giá',
            'Quản trị FAQ',
            'Quản trị Thông tin',
            'Quản trị Đơn hàng',
            'Thanh toán',
            'Giới thiệu',
            'Khách hàng liên hệ',
            'Thống kê truy cập',
            'Quản trị Tài khoản (Khách hàng)',
            'Quản trị Tài khoản (Quản trị viên)',
            'Quản trị Sản phẩm (Thống kê)',
            'Quản trị Sản phẩm',
            'Quản trị Sản phẩm (Kho)',
            'Quản trị Sản phẩm (Bình luận)',
            'Quản trị Bài viết',
            'Thống kê doanh thu',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
    }
}

