<?php

namespace Database\Seeders;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [   
                'name' => 'Tấn Duy',
                'username' => 'admin',
                'email' => 'sneakersquare.demo@gmail.com',
                'password' => Hash::make('SneakerSquare@#'), 
                'user_role' => 1,
                'created_at' => Now(), 
                'updated_at' => Now()
            ],
        ]);

        // $ho = ['Nguyễn', 'Trần', 'Hoàng', 'Lê', 'Tô', 'Bùi'];
        // $dem = ['Bảo', 'Trung', 'Ngọc', 'Minh', 'Thu', 'Hương'];
        // $ten = ['Kiệt', 'Dũng', 'Huy', 'Ái', 'Phụng', 'Vy'];
        // $dotmailSeed = ['@gmail.com', ''];
        
        // for ($i = 0; $i < 5; $i++) {
        //     $hoRand = Arr::random($ho);
        //     $demRand = Arr::random($dem);
        //     $tenRand = Arr::random($ten);
        //     $fullname = $hoRand. ' ' . $demRand . ' ' . $tenRand;
        //     $mail = Str::slug($hoRand).Str::slug($demRand).Str::slug($tenRand).rand(0, 99999);
        //     $dotmail = Arr::random($dotmailSeed);
        //     DB::table('users')->insert([
        //         [   
        //             'name' => $fullname,
        //             'email' => $mail.$dotmail,
        //             'password' => Hash::make('SneakerSquare@#'), 
        //             'user_role' => 0,
        //             'created_at' => Now(), 
        //             'updated_at' => Now()
        //         ],
        //     ]);
        // }
    }
}
