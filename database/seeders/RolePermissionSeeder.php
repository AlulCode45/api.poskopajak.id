<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Report permissions
            'view reports',
            'view own reports',
            'create reports',
            'edit reports',
            'edit own reports',
            'delete reports',
            'delete own reports',
            'change report status',

            // User management permissions
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Dashboard permissions
            'view dashboard',
            'view statistics',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions

        // Admin role - has all permissions
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        // Moderator role - can manage reports
        $moderatorRole = Role::create(['name' => 'moderator', 'guard_name' => 'web']);
        $moderatorRole->givePermissionTo([
            'view reports',
            'edit reports',
            'change report status',
            'view dashboard',
            'view statistics',
        ]);

        // User role - basic permissions
        $userRole = Role::create(['name' => 'user', 'guard_name' => 'web']);
        $userRole->givePermissionTo([
            'view own reports',
            'create reports',
            'edit own reports',
            'delete own reports',
            'view dashboard',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
