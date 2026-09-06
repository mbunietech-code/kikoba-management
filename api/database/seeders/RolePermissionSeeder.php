<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'members.view', 'members.create', 'members.update', 'members.delete',
        'shares.view', 'shares.create',
        'savings.view', 'savings.deposit', 'savings.withdraw',
        'loans.view', 'loans.apply', 'loans.review', 'loans.approve', 'loans.reject', 'loans.disburse', 'loans.repay',
        'products.view', 'products.manage',
        'guarantors.view', 'guarantors.verify',
        'projects.view', 'projects.manage', 'projects.invest',
        'insurance.view', 'insurance.manage', 'insurance.claims',
        'payments.view', 'payments.verify',
        'accounting.view', 'accounting.post',
        'reports.view',
        'profit.view', 'profit.manage',
        'notifications.view', 'notifications.send',
        'users.view', 'users.manage',
        'roles.manage',
        'settings.view', 'settings.manage',
        'audit.view',
        'dashboard.view',
    ];

    public const ROLES = [
        'super_admin' => '*',
        'admin' => [
            'members.*', 'shares.*', 'savings.*', 'loans.*', 'products.*', 'guarantors.*',
            'projects.*', 'insurance.*', 'payments.*', 'reports.view', 'profit.*',
            'notifications.*', 'users.*', 'roles.manage', 'settings.*', 'audit.view', 'dashboard.view',
        ],
        'treasurer' => [
            'members.view', 'savings.*', 'payments.*', 'loans.view', 'loans.disburse', 'loans.repay',
            'reports.view', 'dashboard.view', 'shares.view', 'shares.create',
        ],
        'accountant' => [
            'members.view', 'accounting.*', 'reports.view', 'payments.view', 'loans.view',
            'savings.view', 'profit.*', 'dashboard.view',
        ],
        'loan_officer' => [
            'members.view', 'loans.view', 'loans.review', 'loans.approve', 'loans.reject',
            'guarantors.*', 'products.view', 'reports.view', 'dashboard.view',
        ],
        'member' => [
            'members.view', 'shares.view', 'savings.view', 'loans.view', 'loans.apply',
            'projects.view', 'projects.invest', 'insurance.view', 'insurance.claims', 'dashboard.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (self::ROLES as $roleName => $grants) {
            $role = Role::findOrCreate($roleName, 'web');

            if ($grants === '*') {
                $role->syncPermissions(Permission::all());

                continue;
            }

            $resolved = collect($grants)->flatMap(function ($pattern) {
                if (str_ends_with($pattern, '.*')) {
                    $prefix = substr($pattern, 0, -1);

                    return collect(self::PERMISSIONS)->filter(fn ($p) => str_starts_with($p, $prefix));
                }

                return [$pattern];
            })->unique()->values();

            $role->syncPermissions($resolved->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
