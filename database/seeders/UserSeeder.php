<?php

namespace Database\Seeders;

use App\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserModel::updateOrCreate(
            ['email' => 'sneakersquare.demo@gmail.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => 'SneakerSquare@#',
                'user_role' => 1,
            ]
        );

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
