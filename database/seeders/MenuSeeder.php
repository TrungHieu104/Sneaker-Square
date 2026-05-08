<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('menu')->insert([
            [
                'menu_id' => 1,
                'menu_name' => 'Trang chủ',
                'menu_link' => '/',
                'menu_hidden' => 1,
                'menu_position' => 1,
                'menu_parent_id' => null,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 2,
                'menu_name' => 'Giới thiệu',
                'menu_link' => '/ve-chung-toi',
                'menu_hidden' => 1,
                'menu_position' => 2,
                'menu_parent_id' => null,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 3,
                'menu_name' => 'Sản phẩm',
                'menu_link' => '/san-pham',
                'menu_hidden' => 1,
                'menu_position' => 3,
                'menu_parent_id' => null,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 4,
                'menu_name' => 'Thương hiệu',
                'menu_link' => '#',
                'menu_hidden' => 1,
                'menu_position' => 4,
                'menu_parent_id' => null,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 5,
                'menu_name' => 'Phụ kiện',
                'menu_link' => '#',
                'menu_hidden' => 1,
                'menu_position' => 5,
                'menu_parent_id' => null,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 6,
                'menu_name' => 'Blog',
                'menu_link' => '/bai-viet',
                'menu_hidden' => 1,
                'menu_position' => 6,
                'menu_parent_id' => null,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 7,
                'menu_name' => 'Liên hệ',
                'menu_link' => '/lien-he',
                'menu_hidden' => 1,
                'menu_position' => 7,
                'menu_parent_id' => null,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 8,
                'menu_name' => 'Nike',
                'menu_link' => '/danh-muc/nike',
                'menu_hidden' => 1,
                'menu_position' => 8,
                'menu_parent_id' => 4,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 9,
                'menu_name' => 'Adidas',
                'menu_link' => '/danh-muc/adidas',
                'menu_hidden' => 1,
                'menu_position' => 9,
                'menu_parent_id' => 4,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [

                'menu_id' => 10,
                'menu_name' => 'New Balance',
                'menu_link' => '/danh-muc/new-balance',
                'menu_hidden' => 1,
                'menu_position' => 10,
                'menu_parent_id' => 4,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 11,
                'menu_name' => 'Puma',
                'menu_link' => '/danh-muc/puma',
                'menu_hidden' => 1,
                'menu_position' => 11,
                'menu_parent_id' => 4,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 12,
                'menu_name' => 'Vớ',
                'menu_link' => '/danh-muc/vo',
                'menu_hidden' => 1,
                'menu_position' => 12,
                'menu_parent_id' => 5,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 13,
                'menu_name' => 'Dây giày',
                'menu_link' => '/danh-muc/day-giay',
                'menu_hidden' => 1,
                'menu_position' => 13,
                'menu_parent_id' => 5,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 14,
                'menu_name' => 'Dung dịch vệ sinh giày',
                'menu_link' => '/danh-muc/dung-dich-ve-sinh-giay',
                'menu_hidden' => 1,
                'menu_position' => 14,
                'menu_parent_id' => 5,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 15,
                'menu_name' => 'Mẹo vặt',
                'menu_link' => '/bai-viet/meo-vat',
                'menu_hidden' => 1,
                'menu_position' => 15,
                'menu_parent_id' => 6,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 16,
                'menu_name' => 'Tin tức',
                'menu_link' => '/bai-viet/tin-tuc',
                'menu_hidden' => 1,
                'menu_position' => 16,
                'menu_parent_id' => 6,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'menu_id' => 17,
                'menu_name' => 'Tư vấn',
                'menu_link' => '/bai-viet/tu-van',
                'menu_hidden' => 1,
                'menu_position' => 17,
                'menu_parent_id' => 6,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

        ]);
    }
}
