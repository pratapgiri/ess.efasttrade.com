<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'manage-wfh-applications', 'module' => 'wfh_applications', 'label' => 'Manage WFH Applications', 'description' => 'Can manage work from home applications'],
            ['name' => 'manage-any-wfh-applications', 'module' => 'wfh_applications', 'label' => 'Manage All WFH Applications', 'description' => 'Manage any work from home applications'],
            ['name' => 'manage-own-wfh-applications', 'module' => 'wfh_applications', 'label' => 'Manage Own WFH Applications', 'description' => 'Manage own work from home applications'],
            ['name' => 'view-wfh-applications', 'module' => 'wfh_applications', 'label' => 'View WFH Applications', 'description' => 'Can view work from home applications'],
            ['name' => 'create-wfh-applications', 'module' => 'wfh_applications', 'label' => 'Create WFH Applications', 'description' => 'Can create work from home applications'],
            ['name' => 'edit-wfh-applications', 'module' => 'wfh_applications', 'label' => 'Edit WFH Applications', 'description' => 'Can edit work from home applications'],
            ['name' => 'delete-wfh-applications', 'module' => 'wfh_applications', 'label' => 'Delete WFH Applications', 'description' => 'Can delete work from home applications'],
            ['name' => 'approve-wfh-applications', 'module' => 'wfh_applications', 'label' => 'Approve WFH Applications', 'description' => 'Can approve work from home applications'],
            ['name' => 'reject-wfh-applications', 'module' => 'wfh_applications', 'label' => 'Reject WFH Applications', 'description' => 'Can reject work from home applications'],
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
        $permissionNames = [
            'manage-wfh-applications',
            'manage-any-wfh-applications',
            'manage-own-wfh-applications',
            'view-wfh-applications',
            'create-wfh-applications',
            'edit-wfh-applications',
            'delete-wfh-applications',
            'approve-wfh-applications',
            'reject-wfh-applications',
        ];

        DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->delete();
    }
};
