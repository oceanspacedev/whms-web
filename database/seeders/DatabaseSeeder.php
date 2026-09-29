<?php

namespace Database\Seeders;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'policies_and_permissions',
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => Utils::getSuperAdminName(), 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        $panelUserRole = Role::firstOrCreate(['name' => Utils::getPanelUserRoleName(), 'guard_name' => 'web']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles([$superAdminRole]);

        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Panel User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles([$panelUserRole]);
    }
}
