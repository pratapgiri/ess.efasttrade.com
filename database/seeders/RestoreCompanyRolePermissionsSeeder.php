<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Restores full permissions for company + employee + manager + hr roles.
 * Use after ClaimRolePermissionsSeeder wiped scoped roles with syncPermissions.
 *
 * Run: php artisan db:seed --class=RestoreCompanyRolePermissionsSeeder --force
 * Then: php artisan permission:cache-reset
 */
class RestoreCompanyRolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Restoring global company role (RoleSeeder)...');
        $this->call(RoleSeeder::class);

        $this->command?->info('Restoring employee, manager, hr roles per company...');
        (new DefaultCompanyUserSeeder)->restoreScopedRolesForAllCompanies();

        $this->call(ClaimRolePermissionsSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $companyRole = Role::where('name', 'company')->where('guard_name', 'web')->first();
        $this->command?->info("Global company role: {$companyRole?->permissions()->count()} permissions.");
        $this->command?->info('Log out and log in again to refresh the sidebar for all roles.');
    }
}
