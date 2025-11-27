<?php

namespace Database\Seeders;

use App\Enums\Instansi;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        // Buat user biasa untuk setiap instansi
        foreach (Instansi::cases() as $instansi) {
            // Membuat slug dari nama instansi untuk email
            $emailSlug = Str::of($instansi->value)->lower()->replace([' ', ',', '.'], '_')->replace('&', 'dan');
            $email = preg_replace('/[^a-z0-9_]/', '', $emailSlug) . '@email.com';

            $user = User::create([
                'name' => $instansi->value,
                'email' => $email,
                'password' => Hash::make('password'), // Ganti dengan password yang aman
            ]);
            $user->assignRole($userRole);
        }
    }
}
