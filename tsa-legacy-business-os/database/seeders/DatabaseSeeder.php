<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SaasPlanSeeder::class);

        $permissions = [
            'manage tenants',
            'manage billing',
            'manage plans',
            'view audit logs',
            'manage inventory',
            'manage sales',
            'manage purchases',
            'manage crm',
            'manage accounting',
            'manage hr',
            'view reports',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $superAdminRole = Role::findOrCreate('super-admin', 'web');
        $superAdminRole->syncPermissions($permissions);

        $adminEmail = env('SEED_SUPER_ADMIN_EMAIL');
        $adminPassword = env('SEED_SUPER_ADMIN_PASSWORD');

        if ($adminEmail && $adminPassword) {
            $admin = User::query()->updateOrCreate([
                'email' => $adminEmail,
            ], [
                'name' => env('SEED_SUPER_ADMIN_NAME', 'TSA Super Admin'),
                'password' => $adminPassword,
                'is_platform_admin' => true,
                'is_active' => true,
            ]);

            $admin->assignRole($superAdminRole);
        } elseif ($this->command) {
            $this->command->warn('Skipping super-admin user seed. Set SEED_SUPER_ADMIN_EMAIL and SEED_SUPER_ADMIN_PASSWORD to create one.');
        }
    }
}
