<?php

namespace Database\Seeders;

use App\Models\Tenant\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage inventory',
            'manage sales',
            'manage purchases',
            'manage crm',
            'manage accounting',
            'manage hr',
            'view reports',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $ownerRole = Role::findOrCreate('owner', 'web');
        $ownerRole->syncPermissions($permissions);

        $ownerEmail = env('SEED_TENANT_OWNER_EMAIL');
        $ownerPassword = env('SEED_TENANT_OWNER_PASSWORD');

        if ($ownerEmail && $ownerPassword) {
            $owner = User::query()->firstOrCreate([
                'email' => $ownerEmail,
            ], [
                'name' => env('SEED_TENANT_OWNER_NAME', 'Tenant Owner'),
                'password' => Hash::make($ownerPassword),
                'is_active' => true,
            ]);

            $owner->assignRole($ownerRole);
        } elseif ($this->command) {
            $this->command->warn('Skipping tenant owner seed. Set SEED_TENANT_OWNER_EMAIL and SEED_TENANT_OWNER_PASSWORD to create one.');
        }
    }
}
