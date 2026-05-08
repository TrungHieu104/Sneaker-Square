<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tags')->insert([

            [
                'tag_content' => 'Sneaker Square',
                'tag_slug' => 'sneaker-square',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'Nike Air',
                'tag_slug' => 'nike-air',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'Adidas',
                'tag_slug' => 'adidas',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'Puma',
                'tag_slug' => 'puma',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'Vớ trắng',
                'tag_slug' => 'vo-trang',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'Hot trend',
                'tag_slug' => 'hot-trend',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'Mới nhất',
                'tag_slug' => 'moi-nhat',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'Hữu ích',
                'tag_slug' => 'huu-ich',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'New',
                'tag_slug' => 'new',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'Phụ kiện',
                'tag_slug' => 'phu-kien',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'Sale',
                'tag_slug' => 'sale',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'tag_content' => 'Giảm giá',
                'tag_slug' => 'giam-gia',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
        ]);
    }
}
