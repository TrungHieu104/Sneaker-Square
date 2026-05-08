<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class CateSlideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cate_slide')->insert([
            [
                'cate_slide_name' => 'Trang chủ',
                'cate_slide_slug' => 'trang-chu',
                'cate_slide_hidden' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
        ]);
    }
}
