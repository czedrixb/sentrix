<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Every permission the application recognises, grouped by the area it guards.
     *
     * @var list<string>
     */
    private const PERMISSIONS = [
        'dashboard.view',
        'products.view',
        'products.manage',
        'stock.manage',
        'categories.manage',
        'brands.manage',
        'branches.manage',
        'orders.view',
        'orders.manage',
        'vouchers.view',
        'vouchers.manage',
        'inquiries.view',
        'inquiries.manage',
        'content.view',
        'content.manage',
        'careers.manage',
        'users.manage',
    ];

    /**
     * Role definitions. A role listed with ['*'] receives every permission.
     *
     * @var array<string, list<string>>
     */
    private const ROLES = [
        'admin' => ['*'],
        'sales' => [
            'dashboard.view', 'products.view', 'orders.view', 'orders.manage',
            'inquiries.view', 'inquiries.manage', 'content.view',
        ],
        'inventory' => [
            'dashboard.view', 'products.view', 'products.manage', 'stock.manage',
            'categories.manage', 'brands.manage', 'orders.view',
        ],
        'accounts' => [
            'dashboard.view', 'products.view', 'orders.view', 'orders.manage',
            'vouchers.view', 'vouchers.manage',
        ],
        'auditor' => [
            'dashboard.view', 'products.view', 'orders.view', 'vouchers.view',
            'inquiries.view', 'content.view',
        ],
        'hr' => ['dashboard.view', 'careers.manage', 'content.view'],
        'branch_manager' => [
            'dashboard.view', 'products.view', 'stock.manage',
            'orders.view', 'orders.manage', 'inquiries.view', 'inquiries.manage',
        ],
        'customer' => [],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLES as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            $role->syncPermissions(
                $permissions === ['*'] ? self::PERMISSIONS : $permissions
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
