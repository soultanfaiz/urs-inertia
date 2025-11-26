<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat role 'admin'
        $adminRole = Role::create(['name' => 'admin']);

        // Buat role 'user'
        $userRole = Role::create(['name' => 'user']);

        // Buat user admin
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@email.com',
            'password' => Hash::make('password'), // Ganti dengan password yang aman
        ]);
        $adminUser->assignRole($adminRole);

        // Buat user biasa
        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@email.com',
            'password' => Hash::make('password'), // Ganti dengan password yang aman
        ]);
        $regularUser->assignRole($userRole);

        // Buat user biasa tambahan
        $anotherUser = User::create([
            'name' => 'Regular User Lain',
            'email' => 'user2@email.com',
            'password' => Hash::make('password'), // Ganti dengan password yang aman
        ]);
        $anotherUser->assignRole($userRole);
    }
}
