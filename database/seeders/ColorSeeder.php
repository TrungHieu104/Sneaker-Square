<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('color')->insert([
            ['color' => 'black', 'color_vn' => 'Đen', 'created_at' => Now(), 'updated_at' => Now()],
            ['color' => 'white', 'color_vn' => 'Trắng', 'created_at' => Now(), 'updated_at' => Now()],
            ['color' => 'grey', 'color_vn' => 'Xám', 'created_at' => Now(), 'updated_at' => Now()],
            ['color' => 'red', 'color_vn' => 'Đỏ', 'created_at' => Now(), 'updated_at' => Now()],
            ['color' => 'blue', 'color_vn' => 'Xanh dương', 'created_at' => Now(), 'updated_at' => Now()],
            ['color' => 'green', 'color_vn' => 'Xanh lá', 'created_at' => Now(), 'updated_at' => Now()],
            ['color' => 'pink', 'color_vn' => 'Hồng', 'created_at' => Now(), 'updated_at' => Now()],
            ['color' => 'orange', 'color_vn' => 'Cam', 'created_at' => Now(), 'updated_at' => Now()],
        ]);
    }
}
