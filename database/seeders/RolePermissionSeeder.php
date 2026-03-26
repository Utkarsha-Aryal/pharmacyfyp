<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'inventory.category',
            'inventory.unit',
            'inventory.product',
            'inventory.batch',
            'purchase.supplier',
            'purchase.entry',
            'report.low_stock',
            'report.expiry',
            'user.manage',
            'role.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $staffRole = Role::findOrCreate('staff', 'web');

        $adminRole->syncPermissions($permissions);
        $staffRole->syncPermissions([
            'dashboard.view',
            'inventory.category',
            'inventory.unit',
            'inventory.product',
            'inventory.batch',
            'purchase.supplier',
            'purchase.entry',
            'report.low_stock',
            'report.expiry',
        ]);
    }
}
