<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\LatePenaltyLog;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LatePenaltyApplicator
{
    /**
     * Apply late-arrival penalties for a calendar month (same logic as artisan attendance:apply-late-penalty).
     *
     * @return array{
     *     month_key: string,
     *     year: int,
     *     month: int,
     *     processed: int,
     *     penalized: int,
     *     skipped: int,
     *     errors: int,
     *     no_employees: bool,
     *     warnings: list<string>,
     *     info_lines: list<string>
     * }
     */
    public function run(
        ?int $year,
        ?int $month,
        ?int $companyId,
        int $systemUserId,
        bool $dryRun = false,
        string $leaveTypeName = 'Paid Leave'
    ): array {
        [$year, $month, $startOfMonth, $endOfMonth] = self::resolveMonthWindow($year, $month);

        $monthKey = $startOfMonth->format('Y-m');
        $warnings = [];
        $infoLines = [];

        $employeesQuery = User::where('type', 'employee')
            ->where('status', 'active');

        if ($companyId) {
            $employeesQuery->where('created_by', $companyId);
        }

        $employees = $employeesQuery->get(['id', 'created_by', 'name']);

        if ($employees->isEmpty()) {
            return [
                'month_key' => $monthKey,
                'year' => $year,
                'month' => $month,
                'processed' => 0,
                'penalized' => 0,
                'skipped' => 0,
                'errors' => 0,
                'no_employees' => true,
                'warnings' => [],
                'info_lines' => [],
            ];
        }

        $processed = 0;
        $penalized = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($employees as $employee) {
            $processed++;

            try {
                if ($companyId && (int) $employee->created_by !== $companyId) {
                    $skipped++;
                    $this->writeLog([
                        'employee_id' => $employee->id,
                        'company_id' => $employee->created_by,
                        'year' => $year,
                        'month' => $month,
                        'status' => 'skipped',
                        'message' => 'Skipped due to company scope mismatch.',
                        'meta' => ['month_key' => $monthKey, 'dry_run' => $dryRun],
                    ]);
                    continue;
                }

                $penaltyLeaveType = LeaveType::where('name', $leaveTypeName)
                    ->where('created_by', $employee->created_by)
                    ->first();

                if (! $penaltyLeaveType) {
                    $skipped++;
                    $msg = "No leave type '{$leaveTypeName}' for this company.";
                    $warnings[] = "employee_id={$employee->id}: {$msg}";

                    $this->writeLog([
                        'employee_id' => $employee->id,
                        'company_id' => $employee->created_by,
                        'year' => $year,
                        'month' => $month,
                        'status' => 'skipped',
                        'message' => $msg,
                        'meta' => ['month_key' => $monthKey, 'dry_run' => $dryRun],
                    ]);
                    continue;
                }

                $lateStats = $this->getEmployeeLateStats($employee->id, $startOfMonth, $endOfMonth);
                $lateCount = $lateStats['late_count'];
                $directHalfDayCount = $lateStats['direct_half_day_count'];
                $penaltyUnitDates = $lateStats['penalty_unit_dates'];
                $lateWindowPenaltyDays = max(0, $lateCount - 2) * 0.5;
                $directPenaltyDays = $directHalfDayCount * 0.5;
                $expectedPenaltyDays = round(count($penaltyUnitDates) * 0.5, 2);

                if ($expectedPenaltyDays <= 0) {
                    $skipped++;
                    $this->writeLog([
                        'employee_id' => $employee->id,
                        'company_id' => $employee->created_by,
                        'leave_type_id' => $penaltyLeaveType->id,
                        'year' => $year,
                        'month' => $month,
                        'late_count' => $lateCount,
                        'expected_penalty_days' => $expectedPenaltyDays,
                        'already_applied_days' => 0,
                        'applied_now_days' => 0,
                        'status' => 'skipped',
                        'message' => 'No penalty for this month (<=2 late-window entries and no post-cutoff arrivals).',
                        'meta' => [
                            'month_key' => $monthKey,
                            'dry_run' => $dryRun,
                            'direct_half_day_count' => $directHalfDayCount,
                            'late_window_penalty_days' => $lateWindowPenaltyDays,
                            'direct_penalty_days' => $directPenaltyDays,
                            'excluded_leave_wfh_lop_dates' => $lateStats['excluded_date_keys'],
                        ],
                    ]);
                    continue;
                }

                $excludedDateSet = array_fill_keys($lateStats['excluded_date_keys'], true);
                // Match literal month tag without `[`/`]` in LIKE — SQL Server treats `[]` as pattern ranges.
                $penaltyReasonNeedle = 'AUTO_LATE_PENALTY:'.$monthKey;
                $alreadyAppliedApplications = LeaveApplication::query()
                    ->where('employee_id', $employee->id)
                    ->where('leave_type_id', $penaltyLeaveType->id)
                    ->where('reason', 'like', '%'.$penaltyReasonNeedle.'%')
                    ->get(['id', 'start_date', 'total_days']);

                $alreadyAppliedBreakdown = [
                    'counted' => [],
                    'ignored_excluded_dates' => [],
                ];

                foreach ($alreadyAppliedApplications as $application) {
                    $dateKey = Carbon::parse($application->start_date)->toDateString();
                    $entry = [
                        'application_id' => (int) $application->id,
                        'date' => $dateKey,
                        'days' => round((float) $application->total_days, 2),
                    ];

                    if (isset($excludedDateSet[$dateKey])) {
                        $alreadyAppliedBreakdown['ignored_excluded_dates'][] = $entry;
                    } else {
                        $alreadyAppliedBreakdown['counted'][] = $entry;
                    }
                }

                $alreadyAppliedDays = round((float) collect($alreadyAppliedBreakdown['counted'])->sum('days'), 2);

                $daysToApply = round($expectedPenaltyDays - $alreadyAppliedDays, 2);
                $alreadyAppliedUnits = max(0, (int) floor(round($alreadyAppliedDays * 2, 6)));
                $daysToApplyUnits = max(0, (int) floor(round($daysToApply * 2, 6)));
                $unitDatesToApply = array_slice($penaltyUnitDates, $alreadyAppliedUnits, $daysToApplyUnits);
                $daysToApply = round(count($unitDatesToApply) * 0.5, 2);

                if ($daysToApply <= 0) {
                    $skipped++;
                    $this->writeLog([
                        'employee_id' => $employee->id,
                        'company_id' => $employee->created_by,
                        'leave_type_id' => $penaltyLeaveType->id,
                        'year' => $year,
                        'month' => $month,
                        'late_count' => $lateCount,
                        'expected_penalty_days' => $expectedPenaltyDays,
                        'already_applied_days' => $alreadyAppliedDays,
                        'applied_now_days' => 0,
                        'status' => 'skipped',
                        'message' => 'Penalty already fully applied for this month.',
                        'meta' => [
                            'month_key' => $monthKey,
                            'dry_run' => $dryRun,
                            'excluded_leave_wfh_lop_dates' => $lateStats['excluded_date_keys'],
                            'already_applied_breakdown' => $alreadyAppliedBreakdown,
                            'already_applied_total_candidates' => $alreadyAppliedApplications->count(),
                        ],
                    ]);
                    continue;
                }

                $reason = "[AUTO_LATE_PENALTY:{$monthKey}] late_count={$lateCount}, direct_half_days={$directHalfDayCount}, late_window_penalty={$lateWindowPenaltyDays}, expected_penalty={$expectedPenaltyDays}, applied_now={$daysToApply}";

                if ($dryRun) {
                    $penalized++;
                    $infoLines[] = "DRY-RUN | employee_id={$employee->id} | company_id={$employee->created_by} | late={$lateCount} | post_cutoff={$directHalfDayCount} | expected={$expectedPenaltyDays} | already={$alreadyAppliedDays} | apply={$daysToApply}";

                    $this->writeLog([
                        'employee_id' => $employee->id,
                        'company_id' => $employee->created_by,
                        'leave_type_id' => $penaltyLeaveType->id,
                        'year' => $year,
                        'month' => $month,
                        'late_count' => $lateCount,
                        'expected_penalty_days' => $expectedPenaltyDays,
                        'already_applied_days' => $alreadyAppliedDays,
                        'applied_now_days' => $daysToApply,
                        'status' => 'dry_run',
                        'message' => $reason,
                        'meta' => [
                            'month_key' => $monthKey,
                            'dry_run' => true,
                            'direct_half_day_count' => $directHalfDayCount,
                            'late_window_penalty_days' => $lateWindowPenaltyDays,
                            'direct_penalty_days' => $directPenaltyDays,
                            'excluded_leave_wfh_lop_dates' => $lateStats['excluded_date_keys'],
                            'already_applied_breakdown' => $alreadyAppliedBreakdown,
                            'already_applied_total_candidates' => $alreadyAppliedApplications->count(),
                        ],
                    ]);
                    continue;
                }

                $createdLeaveApplicationId = null;

                DB::transaction(function () use (
                    $employee,
                    $penaltyLeaveType,
                    $systemUserId,
                    $reason,
                    $unitDatesToApply,
                    &$createdLeaveApplicationId
                ) {
                    $policy = LeavePolicy::where('leave_type_id', $penaltyLeaveType->id)
                        ->where('status', 'active')
                        ->where('created_by', $employee->created_by)
                        ->first();

                    if (! $policy) {
                        throw new \RuntimeException(
                            "No active leave policy found for employee_id={$employee->id}, leave_type_id={$penaltyLeaveType->id}, company_id={$employee->created_by}"
                        );
                    }

                    $dateUnits = [];
                    foreach ($unitDatesToApply as $dateStr) {
                        if (! isset($dateUnits[$dateStr])) {
                            $dateUnits[$dateStr] = 0;
                        }
                        $dateUnits[$dateStr]++;
                    }
                    ksort($dateUnits);

                    foreach ($dateUnits as $penaltyDate => $units) {
                        $daysForDate = round($units * 0.5, 2);
                        if ($daysForDate <= 0) {
                            continue;
                        }

                        $leaveApplication = LeaveApplication::create([
                            'employee_id' => $employee->id,
                            'leave_type_id' => $penaltyLeaveType->id,
                            'leave_policy_id' => $policy->id,
                            'start_date' => $penaltyDate,
                            'end_date' => $penaltyDate,
                            'total_days' => $daysForDate,
                            'half_day_part' => abs($daysForDate - 0.5) < 0.001 ? 'first_half' : null,
                            'reason' => $reason.' | penalty_date='.$penaltyDate,
                            'status' => 'approved',
                            'manager_comments' => 'Auto-approved by monthly late penalty command',
                            'approved_by' => $systemUserId,
                            'approved_at' => now(),
                            'created_by' => $employee->created_by,
                        ]);

                        // Keep attendance register and leave balance synced for each penalty day.
                        $leaveApplication->createAttendanceRecords();
                        $leaveApplication->refresh();

                        if (preg_match('/\[LWP_DAYS:[0-9]+(?:\.[0-9]+)?\]/', (string) ($leaveApplication->manager_comments ?? ''))) {
                            $this->markPenaltyAttendanceAsLop($employee->id, $penaltyDate);
                        }

                        $createdLeaveApplicationId = $leaveApplication->id;
                    }
                });

                $penalized++;
                $infoLines[] = "Applied | employee_id={$employee->id} | company_id={$employee->created_by} | days={$daysToApply}";

                $this->writeLog([
                    'employee_id' => $employee->id,
                    'company_id' => $employee->created_by,
                    'leave_type_id' => $penaltyLeaveType->id,
                    'leave_application_id' => $createdLeaveApplicationId,
                    'year' => $year,
                    'month' => $month,
                    'late_count' => $lateCount,
                    'expected_penalty_days' => $expectedPenaltyDays,
                    'already_applied_days' => $alreadyAppliedDays,
                    'applied_now_days' => $daysToApply,
                    'status' => 'applied',
                    'message' => $reason,
                    'meta' => [
                        'month_key' => $monthKey,
                        'dry_run' => false,
                        'direct_half_day_count' => $directHalfDayCount,
                        'late_window_penalty_days' => $lateWindowPenaltyDays,
                        'direct_penalty_days' => $directPenaltyDays,
                        'excluded_leave_wfh_lop_days' => $lateStats['excluded_days'],
                        'excluded_leave_wfh_lop_dates' => $lateStats['excluded_date_keys'],
                        'already_applied_breakdown' => $alreadyAppliedBreakdown,
                        'already_applied_total_candidates' => $alreadyAppliedApplications->count(),
                    ],
                ]);
            } catch (\Throwable $e) {
                $errors++;
                $warnings[] = "Failed | employee_id={$employee->id} | {$e->getMessage()}";

                $this->writeLog([
                    'employee_id' => $employee->id,
                    'company_id' => $employee->created_by ?? 0,
                    'year' => $year,
                    'month' => $month,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                    'meta' => ['month_key' => $monthKey, 'dry_run' => $dryRun],
                ]);
            }
        }

        return [
            'month_key' => $monthKey,
            'year' => $year,
            'month' => $month,
            'processed' => $processed,
            'penalized' => $penalized,
            'skipped' => $skipped,
            'errors' => $errors,
            'no_employees' => false,
            'warnings' => $warnings,
            'info_lines' => $infoLines,
        ];
    }

    /**
     * @return array{0:int,1:int,2:Carbon,3:Carbon}
     */
    public static function resolveMonthWindow(?int $year, ?int $month): array
    {
        if ($year !== null || $month !== null) {
            if ($year === null || $month === null) {
                throw new \InvalidArgumentException('Both year and month are required together.');
            }

            if ($month < 1 || $month > 12) {
                throw new \InvalidArgumentException('Month must be between 1 and 12.');
            }

            $start = Carbon::create($year, $month, 1)->startOfDay();
        } else {
            $start = now()->subMonthNoOverflow()->startOfMonth()->startOfDay();
            $year = (int) $start->year;
            $month = (int) $start->month;
        }

        $end = $start->copy()->endOfMonth()->endOfDay();

        return [$year, $month, $start, $end];
    }

    /**
     * Calculate monthly late stats:
     * - late_count: clock-in after 10-minute grace and up to 30 minutes after shift start
     * - direct_half_day_count: immediate half-day triggers:
     *   - clock-in more than 30 minutes after shift start
     *   - clock-in after 12:00 PM
     *   - clock-out before 4:00 PM
     *
     * @return array{
     *     late_count:int,
     *     direct_half_day_count:int,
     *     penalty_unit_dates:list<string>,
     *     excluded_days:int,
     *     excluded_date_keys:list<string>
     * }
     */
    private function getEmployeeLateStats(int $employeeId, Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $leaveDateRanges = $this->buildLeaveDateRanges($employeeId, $startOfMonth, $endOfMonth);
        $wfhDateRanges = $this->buildWfhDateRanges($employeeId, $startOfMonth, $endOfMonth);

        $records = AttendanceRecord::with('shift')
            ->where('employee_id', $employeeId)
            ->whereYear('date', (int) $startOfMonth->year)
            ->whereMonth('date', (int) $startOfMonth->month)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereNotNull('clock_in')
            ->where('is_weekend', false)
            ->where('is_holiday', false)
            ->get(['id', 'date', 'clock_in', 'clock_out', 'shift_id', 'status', 'notes', 'is_weekend', 'is_holiday']);

        $lateCount = 0;
        $directHalfDayCount = 0;
        $penaltyUnitDates = [];
        $lateWindowDates = [];
        $excludedDateKeys = [];
        foreach ($leaveDateRanges as $range) {
            $cursor = Carbon::parse($range['start'])->startOfDay();
            $rangeEnd = Carbon::parse($range['end'])->startOfDay();
            while ($cursor->lte($rangeEnd)) {
                if ($cursor->between($startOfMonth->copy()->startOfDay(), $endOfMonth->copy()->startOfDay(), true)) {
                    $excludedDateKeys[$cursor->toDateString()] = true;
                }
                $cursor->addDay();
            }
        }
        foreach ($wfhDateRanges as $range) {
            $cursor = Carbon::parse($range['start'])->startOfDay();
            $rangeEnd = Carbon::parse($range['end'])->startOfDay();
            while ($cursor->lte($rangeEnd)) {
                if ($cursor->between($startOfMonth->copy()->startOfDay(), $endOfMonth->copy()->startOfDay(), true)) {
                    $excludedDateKeys[$cursor->toDateString()] = true;
                }
                $cursor->addDay();
            }
        }

        foreach ($records as $record) {
            if (! $record->shift || empty($record->shift->start_time) || empty($record->clock_in)) {
                continue;
            }

            $recordDate = Carbon::parse($record->date)->startOfDay();
            if (! $recordDate->between($startOfMonth->copy()->startOfDay(), $endOfMonth->copy()->startOfDay(), true)) {
                continue;
            }

            $dateKey = $recordDate->toDateString();
            $notes = strtolower((string) ($record->notes ?? ''));
            $isLeaveWfhOrLopStatus = in_array($record->status, ['on_leave', 'half_day'], true)
                && (str_contains($notes, 'leave') || str_contains($notes, 'wfh') || str_contains($notes, 'lop') || str_contains($notes, 'lwp'));

            if (
                $this->isDateInRanges($dateKey, $leaveDateRanges)
                || $this->isDateInRanges($dateKey, $wfhDateRanges)
                || $isLeaveWfhOrLopStatus
            ) {
                $excludedDateKeys[$dateKey] = true;
                continue;
            }

            $shiftStart = Carbon::parse($record->date->format('Y-m-d').' '.$record->shift->start_time);
            $clockIn = Carbon::parse($record->date->format('Y-m-d').' '.$record->clock_in);

            $lateMinutes = $shiftStart->diffInMinutes($clockIn, false);

            if ($lateMinutes > 30) {
                $directHalfDayCount++;
                $penaltyUnitDates[] = $dateKey;
            } elseif ($lateMinutes > 10) {
                $lateCount++;
                $lateWindowDates[] = $dateKey;
            }

            $noonCutoff = Carbon::parse($record->date->format('Y-m-d').' 12:00:00');
            if ($clockIn->gt($noonCutoff)) {
                $directHalfDayCount++;
                $penaltyUnitDates[] = $dateKey;
            }

            if (! empty($record->clock_out)) {
                $clockOut = Carbon::parse($record->date->format('Y-m-d').' '.$record->clock_out);
                $fourPmCutoff = Carbon::parse($record->date->format('Y-m-d').' 16:00:00');

                if ($clockOut->lt($fourPmCutoff)) {
                    $directHalfDayCount++;
                    $penaltyUnitDates[] = $dateKey;
                }
            }
        }

        sort($lateWindowDates);
        foreach ($lateWindowDates as $index => $dateKey) {
            if ($index >= 2) {
                $penaltyUnitDates[] = $dateKey;
            }
        }
        sort($penaltyUnitDates);

        $excludedDays = count($excludedDateKeys);

        return [
            'late_count' => $lateCount,
            'direct_half_day_count' => $directHalfDayCount,
            'penalty_unit_dates' => $penaltyUnitDates,
            'excluded_days' => $excludedDays,
            'excluded_date_keys' => array_keys($excludedDateKeys),
        ];
    }

    /**
     * @return list<array{start:string,end:string}>
     */
    private function buildLeaveDateRanges(int $employeeId, Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $applications = LeaveApplication::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $endOfMonth->toDateString())
            ->whereDate('end_date', '>=', $startOfMonth->toDateString())
            ->get(['start_date', 'end_date']);

        $ranges = [];
        foreach ($applications as $application) {
            $ranges[] = [
                'start' => Carbon::parse($application->start_date)->toDateString(),
                'end' => Carbon::parse($application->end_date)->toDateString(),
            ];
        }

        return $ranges;
    }

    /**
     * @return list<array{start:string,end:string}>
     */
    private function buildWfhDateRanges(int $employeeId, Carbon $startOfMonth, Carbon $endOfMonth): array
    {
        $requests = WorkFromHomeRequest::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $endOfMonth->toDateString())
            ->whereDate('end_date', '>=', $startOfMonth->toDateString())
            ->get(['start_date', 'end_date']);

        $ranges = [];
        foreach ($requests as $request) {
            $ranges[] = [
                'start' => Carbon::parse($request->start_date)->toDateString(),
                'end' => Carbon::parse($request->end_date)->toDateString(),
            ];
        }

        return $ranges;
    }

    /**
     * @param  list<array{start:string,end:string}>  $ranges
     */
    private function isDateInRanges(string $date, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if ($date >= $range['start'] && $date <= $range['end']) {
                return true;
            }
        }

        return false;
    }

    private function markPenaltyAttendanceAsLop(int $employeeId, string $penaltyDate): void
    {
        $record = AttendanceRecord::where('employee_id', $employeeId)
            ->whereDate('date', $penaltyDate)
            ->first();

        if (! $record) {
            return;
        }

        $record->status = $record->status === 'half_day' ? 'half_day' : 'on_leave';
        $record->notes = $record->status === 'half_day'
            ? 'LOP — Half day (Auto Late Penalty)'
            : 'LOP (Auto Late Penalty)';
        $record->save();
    }

    private function writeLog(array $data): void
    {
        LatePenaltyLog::create([
            'employee_id' => $data['employee_id'],
            'company_id' => $data['company_id'],
            'leave_type_id' => $data['leave_type_id'] ?? null,
            'leave_application_id' => $data['leave_application_id'] ?? null,
            'year' => $data['year'],
            'month' => $data['month'],
            'late_count' => $data['late_count'] ?? 0,
            'expected_penalty_days' => $data['expected_penalty_days'] ?? 0,
            'already_applied_days' => $data['already_applied_days'] ?? 0,
            'applied_now_days' => $data['applied_now_days'] ?? 0,
            'status' => $data['status'],
            'message' => $data['message'] ?? null,
            'meta' => $data['meta'] ?? null,
            'processed_at' => now(),
        ]);
    }
}
