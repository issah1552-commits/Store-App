<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionGroups = [
            'dashboard' => ['dashboard.view'],
            'products' => [
                'products.view',
                'products.create',
                'products.update',
                'products.delete',
            ],
            'stores' => ['stores.view'],
            'transfers' => [
                'transfers.view',
                'transfers.create',
                'transfers.approve',
                'transfers.dispatch',
                'transfers.receive',
                'transfers.close_variance',
            ],
            'internal_movements' => [
                'internal_movements.view',
                'internal_movements.create',
                'internal_movements.approve',
                'internal_movements.reverse',
            ],
            'orders' => [
                'orders.view',
                'orders.create',
                'orders.complete',
                'orders.cancel',
            ],
            'invoices' => [
                'invoices.view',
                'invoices.create',
                'invoices.mark_paid',
                'invoices.void',
            ],
            'reports' => ['reports.view'],
            'users' => [
                'users.view',
                'users.create',
                'users.update',
                'users.deactivate',
            ],
            'settings' => ['settings.view'],
        ];

        foreach ($permissionGroups as $group => $permissions) {
            foreach ($permissions as $permissionName) {
                Permission::updateOrCreate(
                    ['name' => $permissionName],
                    [
                        'display_name' => str($permissionName)->after('.')->replace('_', ' ')->title(),
                        'group_name' => $group,
                        'description' => str($permissionName)->replace('.', ' ')->replace('_', ' ')->title(),
                    ],
                );
            }
        }

        $roles = [
            'admin' => [
                'display_name' => 'Admin',
                'description' => 'Full system access',
                'permissions' => Permission::query()->pluck('name')->all(),
            ],
            'warehouse_manager' => [
                'display_name' => 'Warehouse Manager',
                'description' => 'Central warehouse operations manager',
                'permissions' => [
                    'dashboard.view',
                    'products.view',
                    'products.create',
                    'products.update',
                    'stores.view',
                    'transfers.view',
                    'transfers.create',
                    'transfers.dispatch',
                    'reports.view',
                    'settings.view',
                ],
            ],
            'warehouse_user' => [
                'display_name' => 'Warehouse User',
                'description' => 'Warehouse operations staff',
                'permissions' => [
                    'dashboard.view',
                    'products.view',
                    'stores.view',
                    'transfers.view',
                    'transfers.create',
                    'transfers.dispatch',
                    'reports.view',
                ],
            ],
            'shop_manager' => [
                'display_name' => 'Shop Manager',
                'description' => 'Regional shop manager',
                'permissions' => [
                    'dashboard.view',
                    'products.view',
                    'stores.view',
                    'transfers.view',
                    'transfers.receive',
                    'internal_movements.view',
                    'internal_movements.create',
                    'internal_movements.approve',
                    'internal_movements.reverse',
                    'orders.view',
                    'orders.create',
                    'orders.complete',
                    'orders.cancel',
                    'invoices.view',
                    'invoices.create',
                    'invoices.mark_paid',
                    'invoices.void',
                    'reports.view',
                ],
            ],
            'shop_user' => [
                'display_name' => 'Shop User',
                'description' => 'Regional operations user',
                'permissions' => [
                    'dashboard.view',
                    'products.view',
                    'transfers.view',
                    'transfers.receive',
                    'internal_movements.view',
                    'internal_movements.create',
                    'orders.view',
                    'orders.create',
                    'orders.complete',
                    'invoices.view',
                    'invoices.create',
                ],
            ],
            'retail_staff' => [
                'display_name' => 'Retail Staff',
                'description' => 'Frontline sales and retail staff',
                'permissions' => [
                    'dashboard.view',
                    'products.view',
                    'internal_movements.view',
                    'internal_movements.create',
                    'orders.view',
                    'orders.create',
                    'invoices.view',
                    'invoices.create',
                ],
            ],
        ];

        foreach ($roles as $name => $roleData) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                    'is_system' => true,
                ],
            );

            $role->permissions()->sync(
                Permission::query()
                    ->whereIn('name', $roleData['permissions'])
                    ->pluck('id')
                    ->all(),
            );
        }
    }
}
