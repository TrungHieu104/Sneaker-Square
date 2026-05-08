<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class VisitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for($i = 0; $i < 150; $i++) {
            
            $ipRand = rand(0, 255).'.'.rand(0, 255).'.'.rand(0, 255);

            DB::table('visitors')->insert([
                [
                    'visitor_ip' => $ipRand,
                    'visitor_date' => Now(),
                ]
            ]);
        }
    }
}
