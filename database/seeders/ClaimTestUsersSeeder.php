<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates 3 test users for My Claims (HR, Manager, Employee).
 *
 * Run:
 *   php artisan db:seed --class=ClaimRolePermissionsSeeder
 *   php artisan db:seed --class=ClaimTestUsersSeeder
 *   php artisan permission:cache-reset
 *
 * Login password for all: abc@123
 */
class ClaimTestUsersSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'abc@123';

    public function run(): void
    {
        // Full manager/hr/employee menus, then add claim permissions (must not syncPermissions-only)
        $this->call(RestoreCompanyRolePermissionsSeeder::class);

        $company = User::where('type', 'company')->orderBy('id')->first();

        if (! $company) {
            $this->command?->error('No company account found. Run DefaultCompanySeeder first.');

            return;
        }

        $companyId = (int) $company->id;

        $accounts = [
            [
                'name' => 'Claims HR',
                'email' => 'hr@yopmail.com',
                'type' => 'hr',
                'role' => 'hr',
            ],
            [
                'name' => 'Claims Manager',
                'email' => 'menger@yopmail.com',
                'type' => 'manager',
                'role' => 'manager',
            ],
            [
                'name' => 'Claims Employee',
                'email' => 'employee@yomail.com',
                'type' => 'employee',
                'role' => 'employee',
            ],
        ];

        $this->command?->info("Company: {$company->name} (ID {$companyId})");
        $this->command?->info('Password for all users: '.self::DEFAULT_PASSWORD);
        $this->command?->newLine();

        foreach ($accounts as $account) {
            $this->createClaimTestUser($companyId, $account);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->newLine();
        $this->command?->info('Done. Log in as employee@yomail.com to test My Claims.');
    }

    private function createClaimTestUser(int $companyId, array $account): void
    {
        $user = User::updateOrCreate(
            ['email' => $account['email']],
            [
                'name' => $account['name'],
                'type' => $account['type'],
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'status' => 'active',
                'is_enable_login' => 1,
                'email_verified_at' => now(),
                'created_by' => $companyId,
                'lang' => 'en',
            ]
        );

        $role = Role::where('name', $account['role'])
            ->where('guard_name', 'web')
            ->where('created_by', $companyId)
            ->first();

        if (! $role) {
            $this->command?->warn("  ✗ Role {$account['role']} not found for company {$companyId}");

            return;
        }

        $user->syncRoles([$role]);

        $this->command?->line("  ✓ {$account['role']}: {$account['email']} (user ID {$user->id})");
    }
}
