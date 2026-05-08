<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 36; $i <= 49 ; $i ++) { 
            DB::table('size')->insert([
                ['size' => $i, 'created_at' => Now(), 'updated_at' => Now()],
            ]);
        }
    }
}
