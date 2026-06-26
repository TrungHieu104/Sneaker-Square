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
                'comment_content' => 'Sản phẩm đi rất êm và ôm chân, rất đáng tiền!',
                'rating' => 5,
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
                'comment_content' => 'Giày đẹp, giao hàng nhanh, chất lượng ổn áp.',
                'rating' => 4,
                'comment_hidden' => 1,
                'comment_date' => Now(),
                'pro_id' => 1,
                'user_id' => NULL,
                'comment_name' => 'Ẩn danh',
                'comment_email' => 'anon@example.com',
                'created_at' => Now(), 
                'updated_at' => Now()
            ],

            [   
                'comment_content' => 'Đôi giày tuyệt vời nhất mình từng mua. Đóng gói rất cẩn thận.',
                'rating' => 5,
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
