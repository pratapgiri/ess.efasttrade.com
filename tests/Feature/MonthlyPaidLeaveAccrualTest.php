<?php

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceMonthlyAccrual;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\MonthlyPlAccrualLog;
use App\Models\User;
use App\Services\MonthlyPaidLeaveAccrualService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = User::factory()->create([
        'type' => 'company',
        'created_by' => 0,
    ]);

    $this->leaveType = LeaveType::create([
        'name' => 'Paid Leave Test',
        'description' => 'Test PL',
        'max_days_per_year' => 24,
        'is_paid' => true,
        'color' => '#10b77f',
        'status' => 'active',
        'created_by' => $this->company->id,
    ]);

    $this->leavePolicy = LeavePolicy::create([
        'name' => 'Paid Leave Test Policy',
        'description' => 'Monthly PL',
        'leave_type_id' => $this->leaveType->id,
        'accrual_type' => 'monthly',
        'accrual_rate' => 2,
        'carry_forward_limit' => 0,
        'min_days_per_application' => 1,
        'max_days_per_application' => 2,
        'requires_approval' => true,
        'status' => 'active',
        'created_by' => $this->company->id,
    ]);
});

/**
 * Helper: create an employee with the given joining date.
 */
function createEmployee(User $company, ?string $joiningDate = '2020-01-01'): User
{
    $user = User::factory()->create([
        'type' => 'employee',
        'created_by' => $company->id,
        'status' => 'active',
    ]);

    Employee::create([
        'employee_id' => 'EMP-PL-' . $user->id,
        'user_id' => $user->id,
        'created_by' => $company->id,
        'employee_status' => 'active',
        'date_of_joining' => $joiningDate,
    ]);

    return $user;
}

it('credits a flat 2 days per month to every active employee', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $svc->processMonth(
        2020,
        3,
        $this->leaveType->id,
        $this->company->id,
        [$this->company->id],
        (int) $this->company->id,
        (int) $this->company->id
    );

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $this->leaveType->id)
        ->where('year', 2020)
        ->first();

    expect((float) $balance->allocated_days)->toBe(2.0);
    expect((float) $balance->remaining_days)->toBe(2.0);

    $log = LeaveBalanceMonthlyAccrual::where('employee_id', $employee->id)
        ->where('year', 2020)
        ->where('month', 3)
        ->first();
    expect((float) $log->days_accrued)->toBe(2.0);
});

it('pro-rates the monthly credit when an employee joins mid-month', function () {
    // March has 31 days. Joining on the 20th → 12 days remaining (incl 20).
    // entitled = 2 * 12/31 = 0.774... -> round(1 dp) = 0.8.
    $employee = createEmployee($this->company, '2020-03-20');

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $svc->processMonth(
        2020,
        3,
        $this->leaveType->id,
        $this->company->id,
        [$this->company->id],
        (int) $this->company->id,
        (int) $this->company->id
    );

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $this->leaveType->id)
        ->where('year', 2020)
        ->first();

    expect((float) $balance->allocated_days)->toBe(0.8);
    expect((float) $balance->remaining_days)->toBe(0.8);
});

it('credits zero when the employee has not joined yet in the processed month', function () {
    $employee = createEmployee($this->company, '2020-05-01');

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $svc->processMonth(
        2020,
        3,
        $this->leaveType->id,
        $this->company->id,
        [$this->company->id],
        (int) $this->company->id,
        (int) $this->company->id
    );

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $this->leaveType->id)
        ->where('year', 2020)
        ->first();

    // A row is still created (with zero allocation) so subsequent runs are idempotent.
    expect($balance)->not->toBeNull();
    expect((float) $balance->allocated_days)->toBe(0.0);
    expect((float) $balance->remaining_days)->toBe(0.0);
});

it('clamps a negative remaining balance to zero at month-end', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    // Manually create a balance where the employee has overdrawn by 1 day.
    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2020,
        'allocated_days' => 0,
        'used_days' => 1,
        'remaining_days' => -1,
        'carried_forward' => 0,
        'manual_adjustment' => 0,
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $svc->processMonth(
        2020,
        3,
        $this->leaveType->id,
        $this->company->id,
        [$this->company->id],
        (int) $this->company->id,
        (int) $this->company->id
    );

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $this->leaveType->id)
        ->where('year', 2020)
        ->first();

    // Started at -1, gained +2 from the flat monthly accrual → +1.
    // No clamp needed because remaining became non-negative after the credit.
    expect((float) $balance->allocated_days)->toBe(2.0);
    expect((float) $balance->remaining_days)->toBe(1.0);
});

it('clamps when the deficit is greater than the monthly accrual', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2020,
        'allocated_days' => 0,
        'used_days' => 5,
        'remaining_days' => -5,
        'carried_forward' => 0,
        'manual_adjustment' => 0,
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $svc->processMonth(
        2020,
        3,
        $this->leaveType->id,
        $this->company->id,
        [$this->company->id],
        (int) $this->company->id,
        (int) $this->company->id
    );

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('leave_type_id', $this->leaveType->id)
        ->where('year', 2020)
        ->first();

    // Started at -5, +2 from accrual = -3 remaining, then clamped → 0.
    expect((float) $balance->remaining_days)->toBe(0.0);
    expect((float) $balance->manual_adjustment)->toBe(3.0);
    expect($balance->adjustment_reason)->toContain('MONTH_END_LOP_CLAMP:2020-03');
});

it('year-end reset WIPES every paid-leave balance row to zero, regardless of sign', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2020,
        'allocated_days' => 24,
        'used_days' => 5,
        'remaining_days' => 21,        // positive leftover — should still be wiped
        'carried_forward' => 2,
        'manual_adjustment' => 0,
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $result = $svc->yearEndReset(2020, [$this->company->id]);

    expect($result['wiped'])->toBe(1);
    expect($result['skipped'])->toBe(0);

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('year', 2020)
        ->first();
    expect((float) $balance->allocated_days)->toBe(0.0);
    expect((float) $balance->used_days)->toBe(0.0);
    expect((float) $balance->carried_forward)->toBe(0.0);
    expect((float) $balance->manual_adjustment)->toBe(0.0);
    expect((float) $balance->remaining_days)->toBe(0.0);
    expect($balance->adjustment_reason)->toContain('YEAR_END_WIPE:2020');
});

it('year-end reset is idempotent and reports already-wiped rows', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2020,
        'allocated_days' => 4,
        'used_days' => 5,
        'remaining_days' => 1,
        'carried_forward' => 2,
        'manual_adjustment' => 0,
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $first = $svc->yearEndReset(2020, [$this->company->id]);
    $second = $svc->yearEndReset(2020, [$this->company->id]);

    expect($first['wiped'])->toBe(1);
    expect($first['skipped'])->toBe(0);
    expect($second['wiped'])->toBe(0);
    expect($second['skipped'])->toBe(1);
});

it('monthly accrual after a year-end wipe does NOT re-credit the wiped row', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2020,
        'allocated_days' => 0,
        'used_days' => 0,
        'remaining_days' => 0,
        'carried_forward' => 0,
        'manual_adjustment' => 0,
        'adjustment_reason' => '[YEAR_END_WIPE:2020] Year 2020 closed; balance wiped to zero (no rollover).',
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $svc->processMonth(
        2020,
        12,
        $this->leaveType->id,
        $this->company->id,
        [$this->company->id],
        (int) $this->company->id,
        (int) $this->company->id
    );

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('year', 2020)
        ->first();

    // Still all zeros.
    expect((float) $balance->allocated_days)->toBe(0.0);
    expect((float) $balance->remaining_days)->toBe(0.0);

    $log = MonthlyPlAccrualLog::where('employee_id', $employee->id)
        ->where('year', 2020)
        ->where('month', 12)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->status)->toBe('skipped_after_wipe');
});

it('reset-carry-forward zeros carried_forward and rebuilds remaining for paid types', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2026,
        'allocated_days' => 4,
        'used_days' => 5,
        'remaining_days' => 1,       // 4 + 2 + 0 - 5 = 1 before reset
        'carried_forward' => 2,
        'manual_adjustment' => 0,
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $result = $svc->resetCarryForward(2026, [$this->company->id]);

    expect($result['updated'])->toBe(1);

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('year', 2026)
        ->first();

    expect((float) $balance->carried_forward)->toBe(0.0);
    // 4 + 0 + 0 - 5 = -1 (negative reflects existing overdraw, will be
    // settled by the regular month-end clamp on next accrual run).
    expect((float) $balance->remaining_days)->toBe(-1.0);
    expect($balance->adjustment_reason)->toContain('CARRYFORWARD_RESET:2026');
});

it('reset-carry-forward is idempotent', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2026,
        'allocated_days' => 4,
        'used_days' => 0,
        'remaining_days' => 6,
        'carried_forward' => 2,
        'manual_adjustment' => 0,
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $first = $svc->resetCarryForward(2026, [$this->company->id]);
    $second = $svc->resetCarryForward(2026, [$this->company->id]);

    expect($first['updated'])->toBe(1);
    expect($second['updated'])->toBe(0);
});

it('re-running the same month does not double-credit', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    $svc = app(MonthlyPaidLeaveAccrualService::class);

    for ($i = 0; $i < 3; $i++) {
        $svc->processMonth(
            2020,
            3,
            $this->leaveType->id,
            $this->company->id,
            [$this->company->id],
            (int) $this->company->id,
            (int) $this->company->id
        );
    }

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('year', 2020)
        ->first();
    expect((float) $balance->allocated_days)->toBe(2.0);
    expect(MonthlyPlAccrualLog::where('employee_id', $employee->id)->count())->toBe(3);
});

/**
 * Helper: seed a balance row + an approved leave application, without going
 * through the controller so we can isolate the model behavior.
 */
function seedBalanceAndApprovedLeave(
    User $company,
    User $employee,
    LeaveType $leaveType,
    LeavePolicy $leavePolicy,
    int $year,
    float $allocated,
    float $totalDays,
    string $startDate,
    ?string $endDate = null
): array {
    $balance = LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'leave_policy_id' => $leavePolicy->id,
        'year' => $year,
        'allocated_days' => $allocated,
        'used_days' => 0,
        'remaining_days' => $allocated,
        'carried_forward' => 0,
        'manual_adjustment' => 0,
        'created_by' => $company->id,
    ]);

    $app = LeaveApplication::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'leave_policy_id' => $leavePolicy->id,
        'start_date' => $startDate,
        'end_date' => $endDate ?? $startDate,
        'total_days' => $totalDays,
        'reason' => 'test',
        'status' => 'approved',
        'created_by' => $company->id,
    ]);

    return [$balance, $app];
}

it('applyToBalance is idempotent and stamps balance_deducted_at exactly once', function () {
    $employee = createEmployee($this->company, '2020-01-01');
    [$balance, $app] = seedBalanceAndApprovedLeave(
        $this->company,
        $employee,
        $this->leaveType,
        $this->leavePolicy,
        2026,
        4.0,
        3.0,
        '2026-04-01',
        '2026-04-03'
    );

    expect($app->applyToBalance())->toBeTrue();
    expect($app->applyToBalance())->toBeFalse();
    expect($app->applyToBalance())->toBeFalse();

    $balance->refresh();
    expect((float) $balance->used_days)->toBe(3.0);
    expect((float) $balance->remaining_days)->toBe(1.0);
    expect($app->fresh()->balance_deducted_at)->not->toBeNull();
    expect((float) $app->fresh()->balance_deducted_days)->toBe(3.0);
});

it('revertFromBalance restores used_days and clears the stamp', function () {
    $employee = createEmployee($this->company, '2020-01-01');
    [$balance, $app] = seedBalanceAndApprovedLeave(
        $this->company,
        $employee,
        $this->leaveType,
        $this->leavePolicy,
        2026,
        4.0,
        3.0,
        '2026-04-01',
        '2026-04-03'
    );

    $app->applyToBalance();
    $balance->refresh();
    expect((float) $balance->used_days)->toBe(3.0);

    expect($app->revertFromBalance())->toBeTrue();
    expect($app->revertFromBalance())->toBeFalse();

    $balance->refresh();
    expect((float) $balance->used_days)->toBe(0.0);
    expect((float) $balance->remaining_days)->toBe(4.0);
    expect($app->fresh()->balance_deducted_at)->toBeNull();
    expect($app->fresh()->balance_deducted_days)->toBeNull();
});

it('revertFromBalance uses the snapshotted balance_deducted_days, not the current total_days', function () {
    $employee = createEmployee($this->company, '2020-01-01');
    [$balance, $app] = seedBalanceAndApprovedLeave(
        $this->company,
        $employee,
        $this->leaveType,
        $this->leavePolicy,
        2026,
        4.0,
        3.0,
        '2026-04-01',
        '2026-04-03'
    );

    $app->applyToBalance();
    $balance->refresh();
    expect((float) $balance->used_days)->toBe(3.0);

    // Someone edited total_days directly on the model after the deduction.
    $app->total_days = 999;
    $app->save();

    $app->revertFromBalance();
    $balance->refresh();
    // Reverts the original 3.0, not the corrupted 999.
    expect((float) $balance->used_days)->toBe(0.0);
});

it('does not double-count used_days if applyToBalance is called twice', function () {
    $employee = createEmployee($this->company, '2020-01-01');
    [$balance, $app] = seedBalanceAndApprovedLeave(
        $this->company,
        $employee,
        $this->leaveType,
        $this->leavePolicy,
        2026,
        4.0,
        2.0,
        '2026-04-01',
        '2026-04-02'
    );

    $app->applyToBalance();
    $app->applyToBalance();

    $balance->refresh();
    expect((float) $balance->used_days)->toBe(2.0);
});

it('floors allocated_days at zero when a negative delta would push it below zero', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    // Seed: previous accrual already credited 2 days for this month and
    // balance is currently at 0 (admin manually edited it back to 0 via UI).
    LeaveBalanceMonthlyAccrual::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => 2026,
        'month' => 4,
        'working_days' => 0,
        'days_accrued' => 2.0,
        'created_by' => $this->company->id,
    ]);
    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2026,
        'allocated_days' => 0,
        'used_days' => 0,
        'remaining_days' => 0,
        'carried_forward' => 0,
        'manual_adjustment' => 0,
        'created_by' => $this->company->id,
    ]);

    // Now update the joining date so the new entitled = 0.5 for April.
    // Delta = 0.5 - 2 = -1.5 → would push allocated to -1.5, must floor at 0.
    $employee->employee->update(['date_of_joining' => '2026-04-23']);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $svc->processMonth(
        2026,
        4,
        $this->leaveType->id,
        $this->company->id,
        [$this->company->id],
        (int) $this->company->id,
        (int) $this->company->id
    );

    $balance = LeaveBalance::where('employee_id', $employee->id)
        ->where('year', 2026)
        ->first();
    expect((float) $balance->allocated_days)->toBe(0.0);
    expect($balance->adjustment_reason)->toContain('ALLOC_FLOOR:2026-04');
});

it('recomputeBalancesFromApplications rebuilds used_days and clamps remaining at zero', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    // Seed an inflated row: 3 approved applications totalling 6 days, but
    // used_days has somehow grown to 15 (the bug pattern we are fixing).
    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2026,
        'allocated_days' => 4,
        'used_days' => 15,
        'remaining_days' => -11,
        'carried_forward' => 0,
        'manual_adjustment' => 0,
        'created_by' => $this->company->id,
    ]);

    foreach ([[2, '2026-02-01', '2026-02-02'], [3, '2026-03-01', '2026-03-03'], [1, '2026-04-10', '2026-04-10']] as $row) {
        LeaveApplication::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_policy_id' => $this->leavePolicy->id,
            'start_date' => $row[1],
            'end_date' => $row[2],
            'total_days' => $row[0],
            'reason' => 'test',
            'status' => 'approved',
            'created_by' => $this->company->id,
        ]);
    }

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $result = $svc->recomputeBalancesFromApplications(2026, [$this->company->id]);

    expect($result['balances_updated'])->toBe(1);
    expect($result['applications_stamped'])->toBe(3);
    expect($result['clamps_applied'])->toBe(1);

    $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
    expect((float) $balance->used_days)->toBe(6.0);
    expect((float) $balance->remaining_days)->toBe(0.0);
    // 4 allocated + 0 carried + manual(=2) − 6 used = 0
    expect((float) $balance->manual_adjustment)->toBe(2.0);
    expect($balance->adjustment_reason)->toContain('MONTH_END_LOP_CLAMP');
});

it('recomputeBalancesFromApplications strips stale clamps and re-applies a fresh one', function () {
    // Sarabjeet-style scenario: previous run absorbed 15 days into manual_adjustment
    // based on an inflated used_days of 15.5. Real used is only 8 → the stale 15 must
    // be released so the row does not show a phantom positive remaining.
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2026,
        'allocated_days' => 0.5,
        'used_days' => 15.5,
        'remaining_days' => 0,
        'carried_forward' => 0,
        'manual_adjustment' => 15,
        'adjustment_reason' => '[MONTH_END_LOP_CLAMP:2026-04:15] Auto-clamped closing balance to zero; overdrawn days settled as LWP via payroll.',
        'created_by' => $this->company->id,
    ]);

    // Approved leaves only sum to 8 (much less than the 15.5 in used_days).
    LeaveApplication::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-04',
        'total_days' => 4,
        'reason' => 'test',
        'status' => 'approved',
        'created_by' => $this->company->id,
    ]);
    LeaveApplication::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-04',
        'total_days' => 4,
        'reason' => 'test',
        'status' => 'approved',
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $result = $svc->recomputeBalancesFromApplications(2026, [$this->company->id]);

    expect($result['clamps_stripped'])->toBe(1);
    expect((float) $result['clamp_stripped_days'])->toBe(15.0);
    expect($result['clamps_applied'])->toBe(1);
    expect((float) $result['clamp_applied_days'])->toBe(7.5); // 8 − 0.5

    $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
    expect((float) $balance->used_days)->toBe(8.0);
    // 0.5 allocated + 0 carried + manual(=7.5) − 8 used = 0
    expect((float) $balance->manual_adjustment)->toBe(7.5);
    expect((float) $balance->remaining_days)->toBe(0.0);
    expect($balance->adjustment_reason)->not->toContain('[MONTH_END_LOP_CLAMP:2026-04:15]');
    expect($balance->adjustment_reason)->toContain('MONTH_END_LOP_CLAMP'); // a fresh tag for current month
});

it('recomputeBalancesFromApplications leaves remaining at zero when no fresh clamp is needed', function () {
    // After stripping the stale clamp, the math may already balance. No new
    // clamp should be added in that case.
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2026,
        'allocated_days' => 4,
        'used_days' => 10,
        'remaining_days' => 0,
        'carried_forward' => 0,
        'manual_adjustment' => 6,
        'adjustment_reason' => '[MONTH_END_LOP_CLAMP:2026-04:6] Auto-clamped closing balance to zero; overdrawn days settled as LWP via payroll.',
        'created_by' => $this->company->id,
    ]);

    LeaveApplication::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-04',
        'total_days' => 4,
        'reason' => 'test',
        'status' => 'approved',
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $result = $svc->recomputeBalancesFromApplications(2026, [$this->company->id]);

    expect($result['clamps_stripped'])->toBe(1);
    expect($result['clamps_applied'])->toBe(0); // no new clamp — already balanced

    $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
    expect((float) $balance->used_days)->toBe(4.0);
    expect((float) $balance->manual_adjustment)->toBe(0.0);
    expect((float) $balance->remaining_days)->toBe(0.0); // 4 + 0 + 0 − 4
});

it('recomputeBalancesFromApplications preserves non-clamp admin adjustments', function () {
    // manual_adjustment carries 8 days: 3 from admin (no tag) + 5 from a clamp.
    // The strip must subtract only the clamp portion (5), keeping the admin
    // adjustment of 3 intact.
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2026,
        'allocated_days' => 4,
        'used_days' => 9,
        'remaining_days' => 3, // 4 + 0 + 8 − 9
        'carried_forward' => 0,
        'manual_adjustment' => 8,
        'adjustment_reason' => 'Admin bonus +3. [MONTH_END_LOP_CLAMP:2026-04:5] Auto-clamped closing balance to zero; overdrawn days settled as LWP via payroll.',
        'created_by' => $this->company->id,
    ]);

    // Real used is 4 (much less than the 9 in the row).
    LeaveApplication::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-04',
        'total_days' => 4,
        'reason' => 'test',
        'status' => 'approved',
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $svc->recomputeBalancesFromApplications(2026, [$this->company->id]);

    $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
    expect((float) $balance->used_days)->toBe(4.0);
    // Admin bonus of 3 preserved; clamp portion of 5 stripped; remaining is positive
    // so no new clamp added. Final manual = 3.
    expect((float) $balance->manual_adjustment)->toBe(3.0);
    // 4 + 0 + 3 − 4 = 3
    expect((float) $balance->remaining_days)->toBe(3.0);
    expect($balance->adjustment_reason)->toContain('Admin bonus +3');
    expect($balance->adjustment_reason)->not->toContain('[MONTH_END_LOP_CLAMP:2026-04:5]');
});

it('recomputeBalancesFromApplications respects --skip-clamp (rebalanceClamps=false)', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2026,
        'allocated_days' => 4,
        'used_days' => 15,
        'remaining_days' => -11,
        'carried_forward' => 0,
        'manual_adjustment' => 0,
        'created_by' => $this->company->id,
    ]);

    LeaveApplication::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-06',
        'total_days' => 6,
        'reason' => 'test',
        'status' => 'approved',
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $result = $svc->recomputeBalancesFromApplications(
        2026,
        [$this->company->id],
        null,
        false, // floorAllocated
        true,  // paidOnly
        false, // dryRun
        false  // rebalanceClamps OFF
    );

    expect($result['clamps_applied'])->toBe(0);

    $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
    expect((float) $balance->used_days)->toBe(6.0);
    expect((float) $balance->remaining_days)->toBe(-2.0); // negative left intact
});

it('recomputeBalancesFromApplications can floor a negative allocated to zero', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2026,
        'allocated_days' => -1.5,
        'used_days' => 0,
        'remaining_days' => 0,
        'carried_forward' => 0,
        'manual_adjustment' => 1.5,
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $result = $svc->recomputeBalancesFromApplications(
        2026,
        [$this->company->id],
        null,
        true // floorAllocated
    );

    expect($result['allocated_floored'])->toBe(1);

    $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
    expect((float) $balance->allocated_days)->toBe(0.0);
    expect($balance->adjustment_reason)->toContain('ALLOC_FLOOR_RECOMPUTE');
});

it('recomputeBalancesFromApplications --dry-run does not write to the DB', function () {
    $employee = createEmployee($this->company, '2020-01-01');

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'year' => 2026,
        'allocated_days' => 4,
        'used_days' => 99,
        'remaining_days' => -95,
        'carried_forward' => 0,
        'manual_adjustment' => 0,
        'created_by' => $this->company->id,
    ]);

    LeaveApplication::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $this->leaveType->id,
        'leave_policy_id' => $this->leavePolicy->id,
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-02',
        'total_days' => 2,
        'reason' => 'test',
        'status' => 'approved',
        'created_by' => $this->company->id,
    ]);

    $svc = app(MonthlyPaidLeaveAccrualService::class);
    $result = $svc->recomputeBalancesFromApplications(
        2026,
        [$this->company->id],
        null,
        false,
        true,
        true // dryRun
    );

    expect($result['balances_updated'])->toBe(1);

    // No actual writes:
    $balance = LeaveBalance::where('employee_id', $employee->id)->where('year', 2026)->first();
    expect((float) $balance->used_days)->toBe(99.0);
    expect(LeaveApplication::where('employee_id', $employee->id)->first()->balance_deducted_at)->toBeNull();
});
