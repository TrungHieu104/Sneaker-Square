<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CateNewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cate_news')->insert([
            [
                'cate_news_name' => 'Mẹo vặt',
                'cate_news_slug' => 'meo-vat',
                'cate_news_sort' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'cate_news_name' => 'Tin tức',
                'cate_news_slug' => 'tin-tuc',
                'cate_news_sort' => 2,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'cate_news_name' => 'Tư vấn',
                'cate_news_slug' => 'tu-van',
                'cate_news_sort' => 3,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
        ]);
    }
}
