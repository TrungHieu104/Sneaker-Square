<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsByTagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('news_by_tags')->insert([
            [
                'news_id' => 1,
                'tag_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            
            [
                'news_id' => 1,
                'tag_id' => 2,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'news_id' => 1,
                'tag_id' => 8,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'news_id' => 2,
                'tag_id' => 1,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'news_id' => 2,
                'tag_id' => 8,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
            [
                'news_id' => 2,
                'tag_id' => '9',
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'news_id' => 3,
                'tag_id' => 5,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'news_id' => 3,
                'tag_id' => 7,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],

            [
                'news_id' => 3,
                'tag_id' => 8,
                'created_at' => Now(), 
                'updated_at' => Now(),
            ],
        ]);
    }
}
