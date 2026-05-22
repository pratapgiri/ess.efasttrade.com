<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'manage-claims', 'module' => 'claims', 'label' => 'Manage Claims', 'description' => 'Can manage claims'],
            ['name' => 'manage-own-claims', 'module' => 'claims', 'label' => 'Manage Own Claims', 'description' => 'Can manage own claims'],
            ['name' => 'manage-claim-approvals', 'module' => 'claims', 'label' => 'Manage Claim Approvals', 'description' => 'Can approve claims in workflow'],
            ['name' => 'view-claims', 'module' => 'claims', 'label' => 'View Claims', 'description' => 'View claims'],
            ['name' => 'create-claims', 'module' => 'claims', 'label' => 'Create Claims', 'description' => 'Can create claims'],
            ['name' => 'edit-claims', 'module' => 'claims', 'label' => 'Edit Claims', 'description' => 'Can edit claims'],
            ['name' => 'delete-claims', 'module' => 'claims', 'label' => 'Delete Claims', 'description' => 'Can delete claims'],
        ];

        foreach ($permissions as $permission) {
            $existing = DB::table('permissions')
                ->where('name', $permission['name'])
                ->where('guard_name', 'web')
                ->first();

            if ($existing) {
                DB::table('permissions')
                    ->where('id', $existing->id)
                    ->update([
                        'module' => $permission['module'],
                        'label' => $permission['label'],
                        'description' => $permission['description'],
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('permissions')->insert([
                    'module' => $permission['module'],
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                    'label' => $permission['label'],
                    'description' => $permission['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $companyRole = DB::table('roles')
            ->where('name', 'company')
            ->where('guard_name', 'web')
            ->first();

        if ($companyRole) {
            foreach (array_column($permissions, 'name') as $permissionName) {
                $permission = DB::table('permissions')
                    ->where('name', $permissionName)
                    ->where('guard_name', 'web')
                    ->first();

                if (! $permission) {
                    continue;
                }

                $existsPivot = DB::table('role_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->where('role_id', $companyRole->id)
                    ->exists();

                if (! $existsPivot) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $companyRole->id,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $names = [
            'manage-claims',
            'manage-own-claims',
            'manage-claim-approvals',
            'view-claims',
            'create-claims',
            'edit-claims',
            'delete-claims',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $names)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
};
