<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Buat roles jika belum ada
        $roles = ['admin', 'sales', 'user'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
        }

        // Buat pengguna dan assign role
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'username' => 'admin',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $sales = User::create([
            'name' => 'Sales User',
            'email' => 'sales@example.com',
            'username' => 'sales',
            'password' => bcrypt('password'),
        ]);
        $sales->assignRole('sales');

        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'username' => 'user',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('user');
    }
}
