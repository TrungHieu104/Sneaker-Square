<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $img = array('/backend/uploads/product/nike-flex-experience-run-10(2).jpg', '/backend/uploads/product/nike-flex-experience-run-10(3).jpg');
        for($i = 0; $i < count($img); $i++) {
            DB::table('image')->insert([
                [
                    'pro_id' => 1, 
                    'img_name' => $img[$i],
                    'created_at' => Now(),
                    'updated_at' => Now(),
                ],
            ]);
        }
    }
}
