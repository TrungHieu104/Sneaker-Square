<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $getUser = DB::table('users')
                        -> where('user_id', 1)
                        -> first();

        DB::table('comments')->insert([
            [   
                'comment_content' => 'Demo comment 1',
                'comment_hidden' => 1,
                'comment_date' => Now(),
                'pro_id' => 1,
                'user_id' => $getUser->user_id,
                'comment_name' => $getUser->name,
                'comment_email' => $getUser->email,
                'created_at' => Now(), 
                'updated_at' => Now()
            ],

            [   
                'comment_content' => 'Demo comment 2',
                'comment_hidden' => 1,
                'comment_date' => Now(),
                'pro_id' => 1,
                'user_id' => NULL,
                'comment_name' => NULL,
                'comment_email' => NULL,
                'created_at' => Now(), 
                'updated_at' => Now()
            ],

            [   
                'comment_content' => 'Demo comment 3',
                'comment_hidden' => 1,
                'comment_date' => Now(),
                'pro_id' => 1,
                'user_id' => NULL,
                'comment_name' => 'Tâm',
                'comment_email' => 'customer07@example.com',
                'created_at' => Now(), 
                'updated_at' => Now()
            ],
        ]);
    }
}
