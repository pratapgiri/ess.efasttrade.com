<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceMonthlyAccrual;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\MonthlyPlAccrualLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Monthly paid leave accrual.
 *
 * Business rule (current):
 *   - Every active/probation employee gets a flat MAX_MONTHLY_DAYS (2) days
 *     of paid leave credited each month, independent of attendance.
 *   - Employees who joined inside the month being processed get a calendar-day
 *     pro-rated credit:  2 * (days_from_join_to_month_end / total_days_in_month),
 *     rounded to 1 decimal.
 *   - After accrual, any negative remaining balance for the month is clamped
 *     to zero by absorbing the deficit into manual_adjustment with an
 *     explanatory reason. The over-drawn portion is already tagged on the
 *     leave application as [LWP_DAYS:n] and gets deducted by payroll, so no
 *     double deduction happens.
 *   - Year-end reset (handled by yearEndReset()) is a FULL WIPE: every paid
 *     leave balance row in the closing year is reset to zero
 *     (allocated_days = used_days = carried_forward = manual_adjustment = 0,
 *     remaining_days = 0) and tagged [YEAR_END_WIPE:YYYY] in
 *     adjustment_reason so subsequent accrual runs know to skip the row.
 *     New-year rows start fresh with carried_forward = 0 — no rollover.
 */
class MonthlyPaidLeaveAccrualService
{
    /** Flat monthly entitlement in days. */
    public const MAX_MONTHLY_DAYS = 2;

    /** Legacy worked-day tier thresholds (kept only for backward compatibility). */
    public const TIER_ONE_MIN_DAYS = 10;

    public const TIER_TWO_MIN_DAYS = 20;

    /**
     * Credit the monthly accrual for one company × one leave type for the
     * given fully-ended month, and clamp any negative balance to zero.
     *
     * Idempotent: re-running for the same year/month only applies the delta
     * vs. what was previously credited.
     *
     * @param  array<int>  $companyUserIds  result of getCompanyAndUsersId()
     * @return array{processed: int, year: int, month: int, leave_type_id: int, batch_id: string, clamped: int}
     */
    public function processMonth(
        int $year,
        int $month,
        int $leaveTypeId,
        int $createdBy,
        array $companyUserIds,
        int $companyIdForLog,
        int $processedBy
    ): array {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException(__('Invalid month.'));
        }

        $monthStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Reject only fully-future months. The new flat-rate logic does not
        // depend on completed attendance, so the current month is fine to
        // process — that's how December gets credited before Dec 31 wipe.
        if ($monthStart->isFuture()) {
            throw new \InvalidArgumentException(__('You cannot process a month that has not started yet.'));
        }

        $leaveType = LeaveType::where('id', $leaveTypeId)
            ->whereIn('created_by', $companyUserIds)
            ->where('status', 'active')
            ->first();

        if (! $leaveType) {
            throw new \InvalidArgumentException(__('Leave type not found or inactive.'));
        }

        $leavePolicy = LeavePolicy::where('leave_type_id', $leaveTypeId)
            ->whereIn('created_by', $companyUserIds)
            ->where('status', 'active')
            ->first();

        if (! $leavePolicy) {
            throw new \InvalidArgumentException(__('No active leave policy for this leave type.'));
        }

        // Load employees with their joining date so we can pro-rate new joiners.
        $employees = User::query()
            ->where('type', 'employee')
            ->whereIn('created_by', $companyUserIds)
            ->whereHas('employee', function ($q) use ($companyUserIds) {
                $q->whereIn('created_by', $companyUserIds)
                    ->whereIn('employee_status', ['active', 'probation']);
            })
            ->with(['employee:id,user_id,date_of_joining,employee_status'])
            ->get(['id']);

        $processed = 0;
        $clamped = 0;
        $skippedAfterWipe = 0;
        $batchId = (string) Str::uuid();
        $wipeMarker = sprintf('[YEAR_END_WIPE:%04d]', $year);

        DB::transaction(function () use (
            $batchId,
            $employees,
            $year,
            $month,
            $leaveTypeId,
            $leavePolicy,
            $monthStart,
            $monthEnd,
            $companyUserIds,
            $createdBy,
            $companyIdForLog,
            $processedBy,
            $wipeMarker,
            &$processed,
            &$clamped,
            &$skippedAfterWipe
        ) {
            foreach ($employees as $employeeUser) {
                $employeeId = (int) $employeeUser->id;
                $joiningDate = optional($employeeUser->employee)->date_of_joining;
                $entitled = $this->entitledDaysForEmployee($joiningDate, $monthStart, $monthEnd);

                $accrual = LeaveBalanceMonthlyAccrual::query()
                    ->where('employee_id', $employeeId)
                    ->where('leave_type_id', $leaveTypeId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->lockForUpdate()
                    ->first();

                $previousCredited = $accrual ? (float) $accrual->days_accrued : 0.0;
                $delta = round($entitled - $previousCredited, 2);

                $balance = LeaveBalance::query()
                    ->where('employee_id', $employeeId)
                    ->where('leave_type_id', $leaveTypeId)
                    ->where('year', $year)
                    ->whereIn('created_by', $companyUserIds)
                    ->lockForUpdate()
                    ->first();

                // If this year's row was already wiped at year-end, do NOT
                // re-credit it. We log a "skipped_after_wipe" row so the audit
                // trail makes the no-op explicit.
                if ($balance && is_string($balance->adjustment_reason) && str_contains($balance->adjustment_reason, $wipeMarker)) {
                    MonthlyPlAccrualLog::create([
                        'batch_id' => $batchId,
                        'company_id' => $companyIdForLog,
                        'employee_id' => $employeeId,
                        'leave_type_id' => $leaveTypeId,
                        'year' => $year,
                        'month' => $month,
                        'working_days' => 0,
                        'entitled_days' => $entitled,
                        'previous_credited_days' => $previousCredited,
                        'delta_applied' => 0,
                        'allocated_after' => (float) $balance->allocated_days,
                        'status' => 'skipped_after_wipe',
                        'message' => sprintf('Balance row for %d was already wiped at year-end; accrual skipped.', $year),
                        'processed_by' => $processedBy,
                        'processed_at' => now(),
                    ]);
                    $skippedAfterWipe++;
                    $processed++;

                    continue;
                }

                if (! $balance) {
                    $balance = LeaveBalance::create([
                        'employee_id' => $employeeId,
                        'leave_type_id' => $leaveTypeId,
                        'leave_policy_id' => $leavePolicy->id,
                        'year' => $year,
                        'allocated_days' => 0,
                        'used_days' => 0,
                        'remaining_days' => 0,
                        'carried_forward' => 0,
                        'manual_adjustment' => 0,
                        'adjustment_reason' => null,
                        'created_by' => $createdBy,
                    ]);
                }

                if ($delta != 0.0) {
                    $rawNewAllocated = round((float) $balance->allocated_days + $delta, 2);

                    // Floor allocated_days at 0. A negative delta (entitled < previously
                    // credited, e.g. joining date corrected after a prior over-credit)
                    // must never push the column below zero, otherwise the balance row
                    // displays a non-sensical negative "Allocated".
                    if ($rawNewAllocated < 0) {
                        $floorTag = sprintf('[ALLOC_FLOOR:%04d-%02d:%s]', $year, $month, abs($rawNewAllocated));
                        $existingReason = (string) ($balance->adjustment_reason ?? '');
                        if (! str_contains($existingReason, $floorTag)) {
                            $balance->adjustment_reason = trim(
                                $existingReason . ' ' . $floorTag
                                . ' Allocated days floored to 0 to absorb a negative accrual delta.'
                            );
                        }
                        $balance->allocated_days = 0;
                    } else {
                        $balance->allocated_days = $rawNewAllocated;
                    }

                    $balance->remaining_days = $this->computeRemainingDays($balance);
                    $balance->save();
                }

                LeaveBalanceMonthlyAccrual::query()->updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'leave_type_id' => $leaveTypeId,
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        // working_days is no longer used to gate entitlement, but the column
                        // still exists; record 0 to keep the audit row shape intact.
                        'working_days' => 0,
                        'days_accrued' => $entitled,
                        'created_by' => $createdBy,
                    ]
                );

                // Month-end LOP clamp: if the employee took more leave than they had,
                // remaining_days will be negative right now. Absorb the deficit into
                // manual_adjustment so the closing balance for the month is exactly 0.
                // The over-drawn portion is already tagged [LWP_DAYS:n] on the leave
                // application and will be deducted by payroll, so we are NOT double-
                // deducting; this only normalises the displayed running balance.
                $balance->refresh();
                $clampResult = $this->clampMonthEndBalance($balance, $year, $month);
                if ($clampResult['clamped']) {
                    $clamped++;
                }

                MonthlyPlAccrualLog::create([
                    'batch_id' => $batchId,
                    'company_id' => $companyIdForLog,
                    'employee_id' => $employeeId,
                    'leave_type_id' => $leaveTypeId,
                    'year' => $year,
                    'month' => $month,
                    'working_days' => 0,
                    'entitled_days' => $entitled,
                    'previous_credited_days' => $previousCredited,
                    'delta_applied' => $delta,
                    'allocated_after' => (float) $balance->allocated_days,
                    'status' => $this->logStatus($delta, $clampResult['clamped']),
                    'message' => $clampResult['clamped']
                        ? sprintf('Closing clamp: settled %s LWP day(s) for %04d-%02d.', $clampResult['deficit'], $year, $month)
                        : null,
                    'processed_by' => $processedBy,
                    'processed_at' => now(),
                ]);

                $processed++;
            }
        });

        return [
            'processed' => $processed,
            'clamped' => $clamped,
            'skipped_after_wipe' => $skippedAfterWipe,
            'year' => $year,
            'month' => $month,
            'leave_type_id' => $leaveTypeId,
            'batch_id' => $batchId,
        ];
    }

    /**
     * Calendar-day pro-rated entitlement for one employee in one month.
     *
     * @param  Carbon|string|null  $joiningDate  Raw column value (Employee::date_of_joining is not cast).
     * @return float Days credited for this month (rounded to 1 decimal).
     */
    public function entitledDaysForEmployee($joiningDate, Carbon $monthStart, Carbon $monthEnd): float
    {
        $join = $this->normalizeJoiningDate($joiningDate);

        // Employee with no recorded (or unparsable) joining date is treated
        // as "joined before the month" and gets the full monthly entitlement.
        if ($join === null) {
            return (float) self::MAX_MONTHLY_DAYS;
        }

        // Joined after this month ends → not yet eligible for this month.
        if ($join->gt($monthEnd)) {
            return 0.0;
        }

        // Joined on or before the month start → full entitlement.
        if ($join->lte($monthStart)) {
            return (float) self::MAX_MONTHLY_DAYS;
        }

        // Joined inside the month → pro-rate by calendar days remaining,
        // INCLUDING the joining day itself.
        $totalDaysInMonth = (int) $monthEnd->day;
        $daysRemainingFromJoin = $monthEnd->diffInDays($join) + 1;

        $entitled = self::MAX_MONTHLY_DAYS * ($daysRemainingFromJoin / $totalDaysInMonth);

        return (float) round($entitled, 1);
    }

    /**
     * Clamp a negative remaining balance to zero by absorbing the deficit
     * into manual_adjustment with a traceable reason.
     *
     * @return array{clamped: bool, deficit: float}
     */
    public function clampMonthEndBalance(LeaveBalance $balance, int $year, int $month): array
    {
        $remaining = (float) $balance->remaining_days;
        if ($remaining >= 0) {
            return ['clamped' => false, 'deficit' => 0.0];
        }

        $deficit = round(abs($remaining), 2);
        $tag = sprintf('[MONTH_END_LOP_CLAMP:%04d-%02d:%s]', $year, $month, $deficit);

        // Skip if we have already clamped for this month (idempotent).
        $existingReason = (string) ($balance->adjustment_reason ?? '');
        if (str_contains($existingReason, sprintf('[MONTH_END_LOP_CLAMP:%04d-%02d:', $year, $month))) {
            return ['clamped' => false, 'deficit' => 0.0];
        }

        $balance->manual_adjustment = round((float) $balance->manual_adjustment + $deficit, 2);
        $balance->adjustment_reason = trim($existingReason . ' ' . $tag . ' Auto-clamped closing balance to zero; overdrawn days settled as LWP via payroll.');
        $balance->remaining_days = $this->computeRemainingDays($balance);
        $balance->save();

        return ['clamped' => true, 'deficit' => $deficit];
    }

    /**
     * Public entry-point: process every active, paid leave type for a company
     * for the given month. Returns aggregated counters.
     *
     * @param  array<int>  $companyUserIds
     * @return array{leave_types: int, processed: int, clamped: int, skipped_after_wipe: int, batch_ids: array<int,string>}
     */
    public function processCompanyMonth(
        int $year,
        int $month,
        int $createdBy,
        array $companyUserIds,
        int $companyIdForLog,
        int $processedBy
    ): array {
        $paidLeaveTypeIds = LeaveType::query()
            ->whereIn('created_by', $companyUserIds)
            ->where('status', 'active')
            ->where('is_paid', true)
            ->whereExists(function ($q) use ($companyUserIds) {
                $q->select(DB::raw(1))
                    ->from('leave_policies')
                    ->whereColumn('leave_policies.leave_type_id', 'leave_types.id')
                    ->whereIn('leave_policies.created_by', $companyUserIds)
                    ->where('leave_policies.status', 'active');
            })
            ->pluck('id');

        $totalProcessed = 0;
        $totalClamped = 0;
        $totalSkipped = 0;
        $batchIds = [];

        foreach ($paidLeaveTypeIds as $leaveTypeId) {
            $result = $this->processMonth(
                $year,
                $month,
                (int) $leaveTypeId,
                $createdBy,
                $companyUserIds,
                $companyIdForLog,
                $processedBy
            );
            $totalProcessed += $result['processed'];
            $totalClamped += $result['clamped'];
            $totalSkipped += $result['skipped_after_wipe'];
            $batchIds[] = $result['batch_id'];
        }

        return [
            'leave_types' => count($paidLeaveTypeIds),
            'processed' => $totalProcessed,
            'clamped' => $totalClamped,
            'skipped_after_wipe' => $totalSkipped,
            'batch_ids' => $batchIds,
        ];
    }

    /**
     * Year-end FULL WIPE for every paid-leave balance row in $year.
     *
     * Sets allocated_days, used_days, carried_forward, manual_adjustment and
     * remaining_days to zero, and appends a [YEAR_END_WIPE:YYYY] marker to
     * adjustment_reason so subsequent monthly-accrual runs know to skip the
     * row (see processMonth()'s "skipped_after_wipe" branch).
     *
     * Idempotent: rows that already carry the year's wipe marker are left
     * untouched.
     *
     * @param  array<int>  $companyUserIds
     * @return array{scanned: int, wiped: int, skipped: int}
     */
    public function yearEndReset(
        int $year,
        array $companyUserIds
    ): array {
        $balances = LeaveBalance::query()
            ->where('year', $year)
            ->whereIn('created_by', $companyUserIds)
            ->whereHas('leaveType', function ($q) {
                $q->where('is_paid', true);
            })
            ->get();

        $wiped = 0;
        $skipped = 0;
        $wipeMarker = sprintf('[YEAR_END_WIPE:%04d]', $year);

        DB::transaction(function () use ($balances, &$wiped, &$skipped, $year, $wipeMarker) {
            foreach ($balances as $balance) {
                $existingReason = (string) ($balance->adjustment_reason ?? '');
                if (str_contains($existingReason, $wipeMarker)) {
                    $skipped++;

                    continue;
                }

                $balance->allocated_days = 0;
                $balance->used_days = 0;
                $balance->carried_forward = 0;
                $balance->manual_adjustment = 0;
                $balance->remaining_days = 0;
                $balance->adjustment_reason = trim(
                    $existingReason . ' ' . $wipeMarker
                    . sprintf(' Year %d closed; balance wiped to zero (no rollover).', $year)
                );
                $balance->save();
                $wiped++;
            }
        });

        return [
            'scanned' => $balances->count(),
            'wiped' => $wiped,
            'skipped' => $skipped,
        ];
    }

    /**
     * One-time cleanup: zero out `carried_forward` on every paid-leave balance
     * row in $year for the given tenant scope, and recompute `remaining_days`.
     * This is intended for the migration to the new "no carry-forward" rule
     * when historic rows had carry-forward set by an earlier convention.
     *
     * Idempotent: rows already at carried_forward = 0 are left untouched.
     *
     * @param  array<int>  $companyUserIds
     * @return array{scanned: int, updated: int}
     */
    public function resetCarryForward(int $year, array $companyUserIds): array
    {
        $balances = LeaveBalance::query()
            ->where('year', $year)
            ->whereIn('created_by', $companyUserIds)
            ->whereHas('leaveType', function ($q) {
                $q->where('is_paid', true);
            })
            ->where('carried_forward', '>', 0)
            ->get();

        $updated = 0;
        $tag = sprintf('[CARRYFORWARD_RESET:%04d:%s]', $year, now()->format('Y-m-d'));

        DB::transaction(function () use ($balances, &$updated, $tag) {
            foreach ($balances as $balance) {
                $existingReason = (string) ($balance->adjustment_reason ?? '');
                if (str_contains($existingReason, '[CARRYFORWARD_RESET:')) {
                    // Already reset previously; ensure value is 0 but don't re-tag.
                    if ((float) $balance->carried_forward > 0) {
                        $balance->carried_forward = 0;
                        $balance->remaining_days = $this->computeRemainingDays($balance);
                        $balance->save();
                        $updated++;
                    }

                    continue;
                }

                $balance->carried_forward = 0;
                $balance->adjustment_reason = trim(
                    $existingReason . ' ' . $tag . ' Carry-forward reset to zero per new no-rollover policy.'
                );
                $balance->remaining_days = $this->computeRemainingDays($balance);
                $balance->save();
                $updated++;
            }
        });

        return [
            'scanned' => $balances->count(),
            'updated' => $updated,
        ];
    }

    /**
     * Rebuild leave_balances.used_days from the source-of-truth leave_applications
     * table for every (employee, leave_type) row in $year. Optional scoping by
     * employee or paid-only types.
     *
     * Also:
     *   - Stamps balance_deducted_at / balance_deducted_days on every approved
     *     application it counts, so subsequent edits flow through
     *     LeaveApplication::applyToBalance() / revertFromBalance() correctly.
     *   - If $rebalanceClamps is true (default), strips every existing
     *     [MONTH_END_LOP_CLAMP:YYYY-MM:x] tag from adjustment_reason, subtracts
     *     each captured deficit from manual_adjustment, then re-applies a
     *     fresh clamp tagged with the current year-month if remaining_days
     *     would otherwise stay negative. This is what guarantees Remaining
     *     never displays a negative number after the cleanup.
     *   - If $floorAllocated is true, clamps any negative allocated_days to 0.
     *
     * @param  array<int>  $companyUserIds
     * @return array{
     *   scanned: int,
     *   balances_updated: int,
     *   applications_stamped: int,
     *   allocated_floored: int,
     *   clamps_stripped: int,
     *   clamp_stripped_days: float,
     *   clamps_applied: int,
     *   clamp_applied_days: float,
     *   details: array<int,array<string,mixed>>
     * }
     */
    public function recomputeBalancesFromApplications(
        int $year,
        array $companyUserIds,
        ?int $employeeId = null,
        bool $floorAllocated = false,
        bool $paidOnly = true,
        bool $dryRun = false,
        bool $rebalanceClamps = true
    ): array {
        $balancesQuery = LeaveBalance::query()
            ->where('year', $year)
            ->whereIn('created_by', $companyUserIds);

        if ($paidOnly) {
            $balancesQuery->whereHas('leaveType', function ($q) {
                $q->where('is_paid', true);
            });
        }

        if ($employeeId !== null) {
            $balancesQuery->where('employee_id', $employeeId);
        }

        $balances = $balancesQuery->get();

        $balancesUpdated = 0;
        $applicationsStamped = 0;
        $allocatedFloored = 0;
        $clampsStripped = 0;
        $clampStrippedDays = 0.0;
        $clampsApplied = 0;
        $clampAppliedDays = 0.0;
        $details = [];

        $stampAt = now();
        $yearStart = sprintf('%04d-01-01', $year);
        $yearEnd = sprintf('%04d-12-31', $year);

        $runner = function () use (
            $balances,
            $companyUserIds,
            $yearStart,
            $yearEnd,
            $floorAllocated,
            $rebalanceClamps,
            $dryRun,
            $stampAt,
            &$balancesUpdated,
            &$applicationsStamped,
            &$allocatedFloored,
            &$clampsStripped,
            &$clampStrippedDays,
            &$clampsApplied,
            &$clampAppliedDays,
            &$details
        ) {
            foreach ($balances as $balance) {
                $approvedApps = LeaveApplication::query()
                    ->where('employee_id', $balance->employee_id)
                    ->where('leave_type_id', $balance->leave_type_id)
                    ->where('status', 'approved')
                    ->whereIn('created_by', $companyUserIds)
                    ->whereBetween('start_date', [$yearStart, $yearEnd])
                    ->get();

                $trueUsed = round((float) $approvedApps->sum(function ($app) {
                    return (float) $app->total_days;
                }), 2);

                $prevUsed = round((float) $balance->used_days, 2);
                $prevAllocated = round((float) $balance->allocated_days, 2);
                $prevManual = round((float) $balance->manual_adjustment, 2);
                $newAllocated = $prevAllocated;
                $allocatedWasFloored = false;

                if ($floorAllocated && $prevAllocated < 0) {
                    $newAllocated = 0.0;
                    $allocatedWasFloored = true;
                }

                // Identify stale LOP-clamp entries baked into adjustment_reason.
                // Each captured deficit was previously added to manual_adjustment;
                // we strip both the tag and the value so a fresh clamp computed
                // against the corrected used_days can replace them. Tags from
                // other sources ([YEAR_END_WIPE], [CARRYFORWARD_RESET], explicit
                // admin edits) are left alone.
                $reasonText = (string) ($balance->adjustment_reason ?? '');
                $clampsStrippedThisRow = 0;
                $clampDaysStrippedThisRow = 0.0;
                if ($rebalanceClamps && $reasonText !== '') {
                    if (preg_match_all('/\[MONTH_END_LOP_CLAMP:\d{4}-\d{2}:([\d.]+)\]/', $reasonText, $matches)) {
                        $clampsStrippedThisRow = count($matches[1]);
                        foreach ($matches[1] as $deficit) {
                            $clampDaysStrippedThisRow += (float) $deficit;
                        }
                        $clampDaysStrippedThisRow = round($clampDaysStrippedThisRow, 2);
                    }
                }
                $newManual = $prevManual;
                if ($clampsStrippedThisRow > 0) {
                    $newManual = round($prevManual - $clampDaysStrippedThisRow, 2);
                    if ($newManual < 0) {
                        // Should not happen unless an admin removed manual_adjustment
                        // directly. Cap at 0 so we don't introduce a phantom negative
                        // adjustment; the fresh clamp below will rebuild what is
                        // actually owed against the corrected used_days.
                        $newManual = 0.0;
                    }
                }

                // Project new remaining BEFORE applying a fresh clamp. If still
                // negative, we will absorb the deficit into manual_adjustment.
                $projectedRemaining = round(
                    ($newAllocated + (float) $balance->carried_forward + $newManual) - $trueUsed,
                    2
                );
                $needsClamp = $rebalanceClamps && $projectedRemaining < 0;
                $freshClampDays = $needsClamp ? round(abs($projectedRemaining), 2) : 0.0;

                $needsBalanceUpdate = ($trueUsed !== $prevUsed)
                    || $allocatedWasFloored
                    || $clampsStrippedThisRow > 0
                    || $needsClamp;

                if ($needsBalanceUpdate && ! $dryRun) {
                    $reasonAccumulator = $reasonText;

                    if ($allocatedWasFloored) {
                        $floorTag = sprintf('[ALLOC_FLOOR_RECOMPUTE:%s:%s]', $stampAt->format('Y-m-d'), $prevAllocated);
                        if (! str_contains($reasonAccumulator, '[ALLOC_FLOOR_RECOMPUTE:')) {
                            $reasonAccumulator = trim(
                                $reasonAccumulator . ' ' . $floorTag
                                . ' Allocated days floored to 0 during balance recompute.'
                            );
                        }
                        $balance->allocated_days = 0;
                    }

                    if ($clampsStrippedThisRow > 0) {
                        $cleaned = preg_replace(
                            '/\s*\[MONTH_END_LOP_CLAMP:\d{4}-\d{2}:[\d.]+\]\s*Auto-clamped closing balance to zero;\s*overdrawn days settled as LWP via payroll\.?\s*/i',
                            ' ',
                            $reasonAccumulator
                        );
                        $cleaned = trim(preg_replace('/\s+/', ' ', (string) ($cleaned ?? '')));
                        $reasonAccumulator = $cleaned;
                        $balance->manual_adjustment = $newManual;
                    }

                    $balance->adjustment_reason = $reasonAccumulator !== '' ? $reasonAccumulator : null;
                    $balance->used_days = $trueUsed;
                    $balance->remaining_days = $this->computeRemainingDays($balance);
                    $balance->save();

                    if ($needsClamp) {
                        // Reuse the existing month-end clamp helper. It tags
                        // adjustment_reason with [MONTH_END_LOP_CLAMP:YYYY-MM:x]
                        // for the CURRENT month so the next monthly run skips
                        // it (idempotent), and absorbs the deficit into
                        // manual_adjustment so Remaining lands at 0.
                        $balance->refresh();
                        $this->clampMonthEndBalance($balance, (int) $stampAt->year, (int) $stampAt->month);
                    }
                }

                if ($needsBalanceUpdate) {
                    $balancesUpdated++;
                }
                if ($allocatedWasFloored) {
                    $allocatedFloored++;
                }
                if ($clampsStrippedThisRow > 0) {
                    $clampsStripped += $clampsStrippedThisRow;
                    $clampStrippedDays += $clampDaysStrippedThisRow;
                }
                if ($needsClamp) {
                    $clampsApplied++;
                    $clampAppliedDays += $freshClampDays;
                }

                $stampedThisRow = 0;
                foreach ($approvedApps as $app) {
                    if ($app->balance_deducted_at !== null
                        && abs((float) ($app->balance_deducted_days ?? 0) - (float) $app->total_days) < 0.005
                    ) {
                        continue;
                    }
                    if (! $dryRun) {
                        $app->balance_deducted_at = $stampAt;
                        $app->balance_deducted_days = round((float) $app->total_days, 2);
                        $app->save();
                    }
                    $stampedThisRow++;
                    $applicationsStamped++;
                }

                $details[] = [
                    'employee_id' => (int) $balance->employee_id,
                    'leave_type_id' => (int) $balance->leave_type_id,
                    'year' => (int) $balance->year,
                    'used_before' => $prevUsed,
                    'used_after' => $trueUsed,
                    'used_delta' => round($trueUsed - $prevUsed, 2),
                    'allocated_before' => $prevAllocated,
                    'allocated_after' => $allocatedWasFloored ? 0.0 : $prevAllocated,
                    'allocated_floored' => $allocatedWasFloored,
                    'manual_before' => $prevManual,
                    'manual_after' => $needsClamp ? round($newManual + $freshClampDays, 2) : $newManual,
                    'clamps_stripped' => $clampsStrippedThisRow,
                    'clamp_stripped_days' => $clampDaysStrippedThisRow,
                    'clamp_applied' => $needsClamp,
                    'clamp_applied_days' => $freshClampDays,
                    'applications_stamped' => $stampedThisRow,
                    'approved_apps_count' => $approvedApps->count(),
                ];
            }
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        return [
            'scanned' => $balances->count(),
            'balances_updated' => $balancesUpdated,
            'applications_stamped' => $applicationsStamped,
            'allocated_floored' => $allocatedFloored,
            'clamps_stripped' => $clampsStripped,
            'clamp_stripped_days' => round($clampStrippedDays, 2),
            'clamps_applied' => $clampsApplied,
            'clamp_applied_days' => round($clampAppliedDays, 2),
            'details' => $details,
        ];
    }

    private function computeRemainingDays(LeaveBalance $balance): float
    {
        return round(
            ((float) $balance->allocated_days
                + (float) $balance->carried_forward
                + (float) $balance->manual_adjustment)
            - (float) $balance->used_days,
            2
        );
    }

    /**
     * Accept whatever shape the joining date is stored in (string from a raw
     * `date` column, Carbon when the model has casts, DateTime, or null) and
     * normalise to a Carbon at start-of-day, or null when it can't be parsed.
     *
     * @param  mixed  $joiningDate
     */
    private function normalizeJoiningDate($joiningDate): ?Carbon
    {
        if ($joiningDate === null || $joiningDate === '') {
            return null;
        }

        if ($joiningDate instanceof Carbon) {
            return $joiningDate->copy()->startOfDay();
        }

        if ($joiningDate instanceof \DateTimeInterface) {
            return Carbon::instance($joiningDate)->startOfDay();
        }

        try {
            return Carbon::parse((string) $joiningDate)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function logStatus(float $delta, bool $clamped): string
    {
        if (abs($delta) > 0.00001 && $clamped) {
            return 'credited_and_clamped';
        }
        if (abs($delta) > 0.00001) {
            return 'credited';
        }
        if ($clamped) {
            return 'clamped_only';
        }

        return 'no_change';
    }

    /**
     * Legacy worked-day entitlement (kept only so older callers/tests still
     * compile; new code paths go through entitledDaysForEmployee()).
     *
     * @deprecated Use entitledDaysForEmployee() instead.
     */
    public function entitledDays(float $workedDays): int
    {
        if ($workedDays >= self::TIER_TWO_MIN_DAYS) {
            return self::MAX_MONTHLY_DAYS;
        }
        if ($workedDays >= self::TIER_ONE_MIN_DAYS) {
            return 1;
        }

        return 0;
    }
}
