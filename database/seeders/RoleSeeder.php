<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'Quản trị viên',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Cộng tác viên',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Nhân viên',
                'guard_name' => 'web'
            ],
        ]);
    }
}
