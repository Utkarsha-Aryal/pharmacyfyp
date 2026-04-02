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
            'inventory.view',
            'inventory.adjustment',
            'purchase.supplier',
            'purchase.entry',
            'purchase.orders',
            'purchase.receive',
            'purchase.payment',
            'report.low_stock',
            'report.expiry',
            'report.purchases',
            'report.suppliers',
            'party.manage',
            'sales.invoice',
            'sales.return',
            'sales.payment',
            'expense.manage',
            'accounting.ledger',
            'accounting.trial_balance',
            'accounting.cash_book',
            'accounting.bank_book',
            'user.manage',
            'role.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $superAdminRole = Role::findOrCreate('superadmin', 'web');
        $staffRole = Role::findOrCreate('staff', 'web');

        $adminRole->syncPermissions($permissions);
        $superAdminRole->syncPermissions($permissions);
        $staffRole->syncPermissions([
            'dashboard.view',
            'inventory.view',
            'inventory.product',
            'inventory.batch',
            'inventory.adjustment',
            'report.low_stock',
            'report.expiry',
        ]);

        $procurementRole = Role::findOrCreate('procurement', 'web');
        $procurementRole->syncPermissions([
            'dashboard.view',
            'purchase.orders',
            'purchase.receive',
            'purchase.payment',
            'report.low_stock',
            'report.expiry',
            'report.purchases',
            'report.suppliers',
        ]);
    }
}
