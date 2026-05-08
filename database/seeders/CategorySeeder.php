<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('category')->insert([
            [
                'cate_name' => 'Thương hiệu',
                'cate_slug' => 'thuong-hieu',
                'cate_sort' => 1,
                'cate_parent_id' => NULL,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'cate_name' => 'Nike',
                'cate_slug' => 'nike',
                'cate_sort' => 2,
                'cate_parent_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'cate_name' => 'Adidas',
                'cate_slug' => 'adidas',
                'cate_sort' => 3,
                'cate_parent_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'cate_name' => 'New Balance',
                'cate_slug' => 'new-balance',
                'cate_sort' => 4,
                'cate_parent_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'cate_name' => 'Puma',
                'cate_slug' => 'puma',
                'cate_sort' => 5,
                'cate_parent_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'cate_name' => 'Phụ kiện',
                'cate_slug' => 'phu-kien',
                'cate_sort' => 6,
                'cate_parent_id' => NULL,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'cate_name' => 'Vớ',
                'cate_slug' => 'vo',
                'cate_sort' => 7,
                'cate_parent_id' => 6,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'cate_name' => 'Dây giày',
                'cate_slug' => 'day-giay',
                'cate_sort' => 8,
                'cate_parent_id' => 6,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'cate_name' => 'Dung dịch vệ sinh giày',
                'cate_slug' => 'dung-dich-ve-sinh-giay',
                'cate_sort' => 9,
                'cate_parent_id' => 6,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
        ]); 
    }
}
