<?php

namespace Database\Seeders;

use App\Models\UserModel;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate([
            'name' => 'Quản trị viên',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'Cộng tác viên',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'Nhân viên',
            'guard_name' => 'web',
        ]);

        // Sync all permissions to the admin role
        $permissions = Permission::all();
        $adminRole->syncPermissions($permissions);

        // Assign the admin role to the default admin user
        $adminUser = UserModel::where('email', 'sneakersquare.demo@gmail.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
        }
    }
}
