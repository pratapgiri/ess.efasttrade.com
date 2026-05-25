<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds claim module permissions and assigns them to employee, manager, and HR roles
 * for every company in the system.
 *
 * Adds claim permissions to roles (does not remove existing permissions).
 * If manager/hr menus are missing, run RestoreCompanyRolePermissionsSeeder first.
 *
 * Run: php artisan db:seed --class=ClaimRolePermissionsSeeder
 * Then: php artisan permission:cache-reset
 */
class ClaimRolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedClaimPermissions();

        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command?->warn('No company users found. Claim permissions were created but no roles were updated.');

            return;
        }

        foreach ($companies as $company) {
            $this->assignRolePermissions($company->id, 'employee', $this->employeeClaimPermissions());
            $this->assignRolePermissions($company->id, 'manager', $this->managerClaimPermissions());
            $this->assignRolePermissions($company->id, 'hr', $this->hrClaimPermissions());
        }

        // Add claim permissions to global company role — do NOT syncPermissions (that wipes HR/menu perms)
        $companyRole = Role::where('name', 'company')->where('guard_name', 'web')->first();
        if ($companyRole) {
            $claimPermissions = Permission::whereIn('name', $this->allClaimPermissionNames())
                ->where('guard_name', 'web')
                ->get();
            $companyRole->givePermissionTo($claimPermissions);
            $this->command?->info('Added claim permissions to company role (existing permissions kept).');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Claim permissions assigned to employee, manager, and HR roles for all companies.');
    }

    private function seedClaimPermissions(): void
    {
        $definitions = [
            ['name' => 'manage-claims', 'module' => 'claims', 'label' => 'Manage Claims', 'description' => 'Can manage all claims'],
            ['name' => 'manage-own-claims', 'module' => 'claims', 'label' => 'Manage Own Claims', 'description' => 'Can manage own claims'],
            ['name' => 'manage-claim-approvals', 'module' => 'claims', 'label' => 'Manage Claim Approvals', 'description' => 'Can approve claims'],
            ['name' => 'view-claims', 'module' => 'claims', 'label' => 'View Claims', 'description' => 'Can view claims'],
            ['name' => 'create-claims', 'module' => 'claims', 'label' => 'Create Claims', 'description' => 'Can create claims'],
            ['name' => 'edit-claims', 'module' => 'claims', 'label' => 'Edit Claims', 'description' => 'Can edit claims'],
            ['name' => 'delete-claims', 'module' => 'claims', 'label' => 'Delete Claims', 'description' => 'Can delete claims'],
        ];

        foreach ($definitions as $def) {
            Permission::updateOrCreate(
                ['name' => $def['name'], 'guard_name' => 'web'],
                [
                    'module' => $def['module'],
                    'label' => $def['label'],
                    'description' => $def['description'],
                ]
            );
        }
    }

    private function assignRolePermissions(int $companyId, string $roleName, array $permissionNames): void
    {
        $role = Role::firstOrCreate(
            [
                'name' => $roleName,
                'guard_name' => 'web',
                'created_by' => $companyId,
            ],
            [
                'label' => ucfirst($roleName),
                'description' => ucfirst($roleName).' role',
                'created_by' => $companyId,
            ]
        );

        $permissions = Permission::whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        if ($permissions->isEmpty()) {
            $this->command?->warn("No permissions found for role {$roleName} (company {$companyId}).");

            return;
        }

        // Add claim permissions without removing existing HR/menu permissions
        $role->givePermissionTo($permissions);

        $this->command?->line("  • {$roleName} @ company {$companyId}: added ".implode(', ', $permissionNames));
    }

    /** Employee — My Claims: create + view own claims only */
    private function employeeClaimPermissions(): array
    {
        return [
            'manage-dashboard',
            'view-dashboard',
            'manage-own-claims',
            'create-claims',
            'view-claims',
        ];
    }

    /** Manager — own claims + view/approve team claims */
    private function managerClaimPermissions(): array
    {
        return [
            'manage-dashboard',
            'view-dashboard',
            'manage-own-claims',
            'create-claims',
            'view-claims',
            'manage-claim-approvals',
        ];
    }

    /** HR — same as manager for claims */
    private function hrClaimPermissions(): array
    {
        return $this->managerClaimPermissions();
    }

    private function allClaimPermissionNames(): array
    {
        return [
            'manage-claims',
            'manage-own-claims',
            'manage-claim-approvals',
            'view-claims',
            'create-claims',
            'edit-claims',
            'delete-claims',
        ];
    }
}
