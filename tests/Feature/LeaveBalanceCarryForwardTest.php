<?php

use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;

it('includes carried forward and manual adjustment when approved leave updates balance', function () {
    $company = User::factory()->create([
        'type' => 'company',
        'created_by' => 0,
    ]);

    $employee = User::factory()->create([
        'type' => 'employee',
        'created_by' => $company->id,
    ]);

    $leaveType = LeaveType::create([
        'name' => 'Annual Leave',
        'description' => 'Test leave type',
        'max_days_per_year' => 10,
        'is_paid' => true,
        'color' => '#3B82F6',
        'status' => 'active',
        'created_by' => $company->id,
    ]);

    $policy = LeavePolicy::create([
        'name' => 'Annual Leave Policy',
        'description' => 'Test leave policy',
        'leave_type_id' => $leaveType->id,
        'accrual_type' => 'yearly',
        'accrual_rate' => 0,
        'carry_forward_limit' => 5,
        'min_days_per_application' => 1,
        'max_days_per_application' => 30,
        'requires_approval' => true,
        'status' => 'active',
        'created_by' => $company->id,
    ]);

    $year = (int) now()->year;

    $balance = LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'leave_policy_id' => $policy->id,
        'year' => $year,
        'allocated_days' => 10,
        'used_days' => 3,
        'remaining_days' => 10, // 10 + 2 + 1 - 3
        'carried_forward' => 2,
        'manual_adjustment' => 1,
        'created_by' => $company->id,
    ]);

    $leave = LeaveApplication::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'leave_policy_id' => $policy->id,
        'start_date' => now()->startOfWeek()->toDateString(),
        'end_date' => now()->startOfWeek()->toDateString(),
        'total_days' => 1,
        'reason' => 'Regression test approved leave',
        'status' => 'approved',
        'approved_by' => $company->id,
        'approved_at' => now(),
        'created_by' => $company->id,
    ]);

    // Simulate approval side-effect path.
    $leave->createAttendanceRecords();

    $balance->refresh();

    expect((float) $balance->used_days)->toBe(4.0);
    expect((float) $balance->remaining_days)->toBe(9.0);
});
