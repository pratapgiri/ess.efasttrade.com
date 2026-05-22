<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionNames = [
            'manage-claims',
            'manage-own-claims',
            'manage-claim-approvals',
            'view-claims',
            'create-claims',
            'edit-claims',
            'delete-claims',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        if ($permissionIds->isEmpty()) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', ['company', 'employee', 'manager', 'hr'])
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Permissions remain; only pivot rows added by this migration are not removed individually.
    }
};
