<?php

namespace App\Http\Controllers;

use App\Models\AttendancePolicy;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularization;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\IpRestriction;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AttendanceRecordController extends Controller
{
    public function employeeMonthlyAttendance(Request $request)
    {
        $user = Auth::user();
        if (! $user || $user->type !== 'employee') {
            return redirect()->route('dashboard')->with('error', __('This page is available for employees only.'));
        }

        if (! ($user->can('manage-own-attendance-records') || $user->can('manage-attendance-records'))) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $month = (int) ($request->get('month', now()->month));
        $year = (int) ($request->get('year', now()->year));
        $safeMonth = min(max($month, 1), 12);
        $startDate = Carbon::create($year, $safeMonth, 1)->startOfMonth()->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        $companyScope = getCompanyAndUsersId();
        if (empty($companyScope)) {
            $companyScope = array_values(array_filter([
                $user->created_by,
                $user->id,
            ]));
        }
        $todayKey = now()->toDateString();
        $allowBackdated = $this->isBackdatedEmployeeRequestAllowed();

        $attendanceRecords = AttendanceRecord::query()
            ->where('employee_id', $user->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['id', 'date', 'clock_in', 'clock_out', 'status', 'notes'])
            ->keyBy(fn ($record) => Carbon::parse($record->date)->toDateString());

        $leaveApplications = LeaveApplication::query()
            ->where('employee_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function ($inner) use ($startDate, $endDate) {
                        $inner->where('start_date', '<=', $startDate->toDateString())
                            ->where('end_date', '>=', $endDate->toDateString());
                    });
            })
            ->get(['id', 'start_date', 'end_date', 'status']);

        $leaveByDate = [];
        foreach ($leaveApplications as $leave) {
            $cursor = Carbon::parse($leave->start_date)->startOfDay();
            $leaveEnd = Carbon::parse($leave->end_date)->endOfDay();
            while ($cursor->lte($leaveEnd)) {
                $dateStr = $cursor->toDateString();
                if ($dateStr >= $startDate->toDateString() && $dateStr <= $endDate->toDateString()) {
                    $leaveByDate[$dateStr] = [
                        'status' => $leave->status,
                        'id' => $leave->id,
                    ];
                }
                $cursor->addDay();
            }
        }

        $regularizations = AttendanceRegularization::query()
            ->where('employee_id', $user->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['id', 'date', 'status'])
            ->keyBy(fn ($regularization) => Carbon::parse($regularization->date)->toDateString());

        $holidays = Holiday::whereIn('created_by', $companyScope)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function ($inner) use ($startDate, $endDate) {
                        $inner->where('start_date', '<=', $startDate->toDateString())
                            ->where(function ($w) use ($endDate) {
                                $w->whereNull('end_date')->orWhere('end_date', '>=', $endDate->toDateString());
                            });
                    });
            })
            ->get(['start_date', 'end_date']);

        $holidayDateKeys = [];
        foreach ($holidays as $holiday) {
            $holidayStart = Carbon::parse($holiday->start_date)->startOfDay();
            $holidayEnd = $holiday->end_date ? Carbon::parse($holiday->end_date)->endOfDay() : $holidayStart->copy()->endOfDay();
            $cursorHoliday = $holidayStart->copy();
            while ($cursorHoliday->lte($holidayEnd)) {
                $holidayDateKeys[$cursorHoliday->toDateString()] = true;
                $cursorHoliday->addDay();
            }
        }

        $closedRuns = PayrollRun::query()
            ->whereIn('created_by', $companyScope)
            ->where('status', 'completed')
            ->whereDate('pay_period_start', '<=', $endDate->toDateString())
            ->whereDate('pay_period_end', '>=', $startDate->toDateString())
            ->get(['pay_period_start', 'pay_period_end']);

        $closedDates = [];
        foreach ($closedRuns as $run) {
            $cursor = Carbon::parse($run->pay_period_start)->startOfDay();
            $closeEnd = Carbon::parse($run->pay_period_end)->endOfDay();
            while ($cursor->lte($closeEnd)) {
                $dateStr = $cursor->toDateString();
                if ($dateStr >= $startDate->toDateString() && $dateStr <= $endDate->toDateString()) {
                    $closedDates[$dateStr] = true;
                }
                $cursor->addDay();
            }
        }

        // Manual month closures by Admin/HR.
        $manualClosedMonths = $this->getClosedAttendanceMonths();
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            if (isset($manualClosedMonths[$cursor->format('Y-m')])) {
                $closedDates[$cursor->toDateString()] = true;
            }
            $cursor->addDay();
        }

        $days = [];
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $dateStr = $cursor->toDateString();
            $record = $attendanceRecords->get($dateStr);
            $isHoliday = isset($holidayDateKeys[$dateStr]) || $cursor->isSunday();
            $leaveData = $leaveByDate[$dateStr] ?? null;
            $regularization = $regularizations->get($dateStr);
            $isClosed = isset($closedDates[$dateStr]);
            $isPastDate = $dateStr < $todayKey;
            $canBackdate = ! $isPastDate || $allowBackdated;
            $canApplyAr = ! $isClosed && $canBackdate && ! $isHoliday;
            $canApplyLeave = ! $isClosed && $canBackdate && ! $leaveData;

            $days[] = [
                'key' => $dateStr,
                'date_label' => $cursor->format('d M'),
                'clock_in' => $record ? $record->clock_in : null,
                'clock_out' => $record ? $record->clock_out : null,
                'status' => $record ? $record->status : null,
                'is_holiday' => $isHoliday,
                'is_month_closed' => $isClosed,
                'leave' => $leaveData,
                'regularization' => $regularization ? [
                    'id' => $regularization->id,
                    'status' => $regularization->status,
                ] : null,
                'attendance_record_id' => $record ? $record->id : null,
                'can_apply_ar' => $canApplyAr,
                'can_apply_leave' => $canApplyLeave,
            ];

            $cursor->addDay();
        }

        $leaveTypeIds = LeavePolicy::query()
            ->whereIn('created_by', $companyScope)
            ->where('status', 'active')
            ->pluck('leave_type_id')
            ->filter()
            ->unique()
            ->values();

        $leaveTypes = LeaveType::query()
            ->whereIn('id', $leaveTypeIds)
            ->where('status', 'active')
            ->get(['id', 'name'])
            ->values();

        if ($leaveTypes->isEmpty()) {
            $leaveTypes = LeaveType::query()
                ->whereIn('created_by', $companyScope)
                ->where('status', 'active')
                ->get(['id', 'name'])
                ->values();
        }

        return Inertia::render('hr/attendance-records/employee-monthly', [
            'days' => $days,
            'leaveTypes' => $leaveTypes,
            'filters' => [
                'month' => $safeMonth,
                'year' => $year,
            ],
            'policy' => [
                'allowBackdatedAttendanceRequests' => $allowBackdated,
            ],
        ]);
    }

    public function musterRoll(Request $request)
    {
        if (! Auth::user()->can('manage-attendance-records')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $user = Auth::user();
        $isCompanyAdmin = $user->type === 'company' || $user->hasRole('company');
        if (! $isCompanyAdmin) {
            return redirect()->route('dashboard')->with('error', __('Attendance Register is only available to company administrators.'));
        }

        $periodType = (string) ($request->get('period_type', 'month'));
        $year = (int) ($request->get('year', now()->year));
        $month = (int) ($request->get('month', now()->month));
        $weekStartInput = $request->get('week_start');

        [$startDate, $endDate, $effectiveWeekStart] = $this->resolveMusterRollWindow($periodType, $year, $month, $weekStartInput);

        $employees = User::emp()
            ->with('employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $attendanceRecords = AttendanceRecord::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['employee_id', 'date', 'clock_in', 'clock_out', 'total_hours', 'status', 'notes']);

        $recordsByEmployeeAndDate = $attendanceRecords
            ->groupBy('employee_id')
            ->map(function ($employeeRecords) {
                return $employeeRecords->keyBy(fn ($record) => Carbon::parse($record->date)->toDateString());
            });

        $holidays = Holiday::whereIn('created_by', getCompanyAndUsersId())
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function ($inner) use ($startDate, $endDate) {
                        $inner->where('start_date', '<=', $startDate->toDateString())
                            ->where(function ($w) use ($endDate) {
                                $w->whereNull('end_date')->orWhere('end_date', '>=', $endDate->toDateString());
                            });
                    });
            })
            ->get(['start_date', 'end_date']);

        $holidayDateKeys = [];
        foreach ($holidays as $holiday) {
            $holidayStart = Carbon::parse($holiday->start_date)->startOfDay();
            $holidayEnd = $holiday->end_date ? Carbon::parse($holiday->end_date)->endOfDay() : $holidayStart->copy()->endOfDay();
            $cursorHoliday = $holidayStart->copy();
            while ($cursorHoliday->lte($holidayEnd)) {
                $holidayDateKeys[$cursorHoliday->toDateString()] = true;
                $cursorHoliday->addDay();
            }
        }

        $isMusterHoliday = function (string $dateStr) use ($holidayDateKeys): bool {
            return isset($holidayDateKeys[$dateStr]) || Carbon::parse($dateStr)->isSunday();
        };

        $leaveByEmployeeAndDate = [];
        $leaveApplications = LeaveApplication::query()
            ->where('status', 'approved')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function ($inner) use ($startDate, $endDate) {
                        $inner->where('start_date', '<=', $startDate->toDateString())
                            ->where('end_date', '>=', $endDate->toDateString());
                    });
            })
            ->get(['employee_id', 'start_date', 'end_date']);

        foreach ($leaveApplications as $leave) {
            $leaveStart = Carbon::parse($leave->start_date)->startOfDay();
            $leaveEnd = Carbon::parse($leave->end_date)->endOfDay();
            $rangeStart = $startDate->copy()->startOfDay()->max($leaveStart);
            $rangeEnd = $endDate->copy()->endOfDay()->min($leaveEnd);
            $cursorLeave = $rangeStart->copy();
            while ($cursorLeave->lte($rangeEnd)) {
                $dateStr = $cursorLeave->toDateString();
                if (! $isMusterHoliday($dateStr)) {
                    $key = $leave->employee_id.'|'.$dateStr;
                    $leaveByEmployeeAndDate[$key] = true;
                }
                $cursorLeave->addDay();
            }
        }

        $lopDaySet = $this->buildMusterRollLopDaySet($employees, $startDate, $endDate);

        $wfhByEmployeeAndDate = [];
        $wfhRequests = WorkFromHomeRequest::query()
            ->where('status', 'approved')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function ($inner) use ($startDate, $endDate) {
                        $inner->where('start_date', '<=', $startDate->toDateString())
                            ->where('end_date', '>=', $endDate->toDateString());
                    });
            })
            ->get(['employee_id', 'start_date', 'end_date']);

        foreach ($wfhRequests as $wfh) {
            $wfhStart = Carbon::parse($wfh->start_date)->startOfDay();
            $wfhEnd = Carbon::parse($wfh->end_date)->endOfDay();
            $rangeStart = $startDate->copy()->startOfDay()->max($wfhStart);
            $rangeEnd = $endDate->copy()->endOfDay()->min($wfhEnd);
            $cursorWfh = $rangeStart->copy();
            while ($cursorWfh->lte($rangeEnd)) {
                $dateStr = $cursorWfh->toDateString();
                if (! $isMusterHoliday($dateStr)) {
                    $key = $wfh->employee_id.'|'.$dateStr;
                    $wfhByEmployeeAndDate[$key] = true;
                }
                $cursorWfh->addDay();
            }
        }

        $days = [];
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $dayKey = $cursor->toDateString();
            $days[] = [
                'key' => $dayKey,
                'day' => $cursor->format('d'),
                'label' => $cursor->format('D'),
                'is_sunday' => $cursor->isSunday(),
                'is_holiday' => isset($holidayDateKeys[$dayKey]),
            ];
            $cursor->addDay();
        }

        $rows = $employees->map(function ($employee) use ($days, $recordsByEmployeeAndDate, $leaveByEmployeeAndDate, $lopDaySet, $wfhByEmployeeAndDate) {
            $daily = [];
            foreach ($days as $day) {
                $employeeRecords = $recordsByEmployeeAndDate->get($employee->id);
                $record = $employeeRecords ? $employeeRecords->get($day['key']) : null;
                $lopKey = $employee->id.'|'.$day['key'];
                $onLeave = isset($leaveByEmployeeAndDate[$lopKey]);
                $onWfh = isset($wfhByEmployeeAndDate[$lopKey]);
                $isHolidayDay = ! empty($day['is_holiday']) || ! empty($day['is_sunday']);
                $lopFraction = (float) ($lopDaySet[$lopKey] ?? 0);
                $isLopDay = ! $isHolidayDay && $lopFraction > 1e-6;

                if ($record) {
                    $leaveLike = in_array($record->status, ['on_leave', 'half_day'], true);
                    $lopDaysForCell = ($isLopDay && $leaveLike) ? min(1.0, $lopFraction) : 0.0;
                    $daily[$day['key']] = [
                        'clock_in' => $record->clock_in,
                        'clock_out' => $record->clock_out,
                        'total_hours' => $record->total_hours,
                        'status' => $record->status,
                        'notes' => $record->notes,
                        'is_lop' => $lopDaysForCell > 1e-6,
                        'lop_days' => round($lopDaysForCell, 4),
                    ];
                } else {
                    $daily[$day['key']] = $onWfh ? [
                        'clock_in' => null,
                        'clock_out' => null,
                        'total_hours' => 0,
                        'status' => 'wfh',
                        'notes' => 'WFH',
                        'is_lop' => false,
                        'lop_days' => 0,
                    ] : ($onLeave ? [
                        'clock_in' => null,
                        'clock_out' => null,
                        'total_hours' => 0,
                        'status' => 'on_leave',
                        'notes' => null,
                        'is_lop' => $isLopDay,
                        'lop_days' => $isLopDay ? round(min(1.0, $lopFraction), 4) : 0,
                    ] : null);
                }
            }

            $joining = optional($employee->employee)->date_of_joining;

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_id' => $employee->employee->employee_id ?? '',
                'date_of_joining' => $joining ? Carbon::parse($joining)->toDateString() : null,
                'daily' => $daily,
            ];
        })->values();

        $selectedMonthKey = sprintf('%04d-%02d', $year, min(max($month, 1), 12));
        $isMonthPeriod = $periodType === 'month';
        $isSelectedMonthClosed = $isMonthPeriod && $this->isMonthManuallyClosed($selectedMonthKey);

        return Inertia::render('hr/attendance-records/muster-roll', [
            'rows' => $rows,
            'days' => $days,
            'filters' => [
                'period_type' => $periodType,
                'month' => $month,
                'year' => $year,
                'week_start' => $effectiveWeekStart,
            ],
            'range' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
            ],
            'monthClosure' => [
                'is_month_period' => $isMonthPeriod,
                'selected_month' => $selectedMonthKey,
                'is_closed' => $isSelectedMonthClosed,
            ],
        ]);
    }

    public function closeMusterMonth(Request $request)
    {
        if (! Auth::user()->can('manage-attendance-records')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $monthKey = sprintf('%04d-%02d', (int) $validated['year'], (int) $validated['month']);
        $closedMonths = $this->getClosedAttendanceMonths();
        $closedMonths[$monthKey] = true;
        updateSetting('closedAttendanceMonths', json_encode(array_keys($closedMonths)));

        return redirect()->back()->with('success', __('Attendance month closed successfully.'));
    }

    public function reopenMusterMonth(Request $request)
    {
        if (! Auth::user()->can('manage-attendance-records')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $monthKey = sprintf('%04d-%02d', (int) $validated['year'], (int) $validated['month']);
        $closedMonths = $this->getClosedAttendanceMonths();
        unset($closedMonths[$monthKey]);
        updateSetting('closedAttendanceMonths', json_encode(array_keys($closedMonths)));

        return redirect()->back()->with('success', __('Attendance month reopened successfully.'));
    }

    private function resolveMusterRollWindow(string $periodType, int $year, int $month, ?string $weekStartInput): array
    {
        if ($periodType === 'week') {
            $weekStart = $weekStartInput
                ? Carbon::parse($weekStartInput)->startOfWeek(Carbon::MONDAY)
                : now()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            return [$weekStart->startOfDay(), $weekEnd->endOfDay(), $weekStart->toDateString()];
        }

        if ($periodType === 'year') {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = Carbon::create($year, 12, 31)->endOfDay();

            return [$start, $end, null];
        }

        $safeMonth = min(max($month, 1), 12);
        $start = Carbon::create($year, $safeMonth, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        return [$start, $end, null];
    }

    /**
     * Per-calendar-day LOP fractions (unpaid leave type, or paid leave after balance exhausted).
     * Values are day fractions (e.g. 0.5 for half-day LWP). Split follows payroll “paid first, LWP last”.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $employees
     * @return array<string, float> keys "employee_id|Y-m-d" => LOP fraction (can be 0.5 for half-day LWP)
     */
    private function buildMusterRollLopDaySet($employees, Carbon $startDate, Carbon $endDate): array
    {
        $employeeIds = $employees->pluck('id')->all();
        if ($employeeIds === []) {
            return [];
        }

        $years = [];
        $walk = $startDate->copy();
        while ($walk->lte($endDate)) {
            $years[(int) $walk->year] = true;
            $walk->addDay();
        }
        $yearList = array_keys($years);
        $globalStart = Carbon::create(min($yearList), 1, 1)->startOfDay();
        $globalEnd = Carbon::create(max($yearList), 12, 31)->endOfDay();

        $allocated = [];
        $balanceRows = LeaveBalance::whereIn('employee_id', $employeeIds)
            ->whereIn('year', $yearList)
            ->get(['employee_id', 'year', 'leave_type_id', 'allocated_days']);
        foreach ($balanceRows as $b) {
            $allocated[$b->employee_id.'|'.(int) $b->year.'|'.$b->leave_type_id] = (float) $b->allocated_days;
        }

        $applications = LeaveApplication::query()
            ->with('leaveType')
            ->where('status', 'approved')
            ->whereIn('employee_id', $employeeIds)
            ->where('start_date', '<=', $globalEnd->toDateString())
            ->where('end_date', '>=', $globalStart->toDateString())
            ->orderByRaw('COALESCE(approved_at, updated_at, created_at) ASC')
            ->orderBy('id')
            ->get();

        $remState = [];
        /** @var array<string, float> */
        $lop = [];
        $musterStartStr = $startDate->toDateString();
        $musterEndStr = $endDate->toDateString();

        foreach ($applications as $app) {
            if (! $app->leaveType) {
                continue;
            }

            $empId = (int) $app->employee_id;
            $typeId = (int) $app->leave_type_id;

            $fullDays = [];
            $cursor = Carbon::parse($app->start_date)->startOfDay();
            $end = Carbon::parse($app->end_date)->startOfDay();
            while ($cursor->lte($end)) {
                $fullDays[] = $cursor->toDateString();
                $cursor->addDay();
            }
            $nFull = count($fullDays);
            if ($nFull < 1) {
                continue;
            }

            $total = (float) $app->total_days;
            $perDay = $total / $nFull;

            if (! $app->leaveType->is_paid) {
                foreach ($fullDays as $dateStr) {
                    if ($dateStr >= $musterStartStr && $dateStr <= $musterEndStr) {
                        $k = $empId.'|'.$dateStr;
                        $lop[$k] = round(($lop[$k] ?? 0) + $perDay, 4);
                    }
                }

                continue;
            }

            $accYear = (int) Carbon::parse($app->start_date)->format('Y');
            $rk = $empId.'|'.$accYear.'|'.$typeId;

            if (! array_key_exists($rk, $remState)) {
                $remState[$rk] = $allocated[$rk] ?? 0.0;
            }

            $r = (float) $remState[$rk];
            $paid = min($total, max(0.0, $r));
            $lwpTotal = $total - $paid;
            $remState[$rk] = $r - $total;

            $lwpLeft = $lwpTotal;
            for ($i = $nFull - 1; $i >= 0 && $lwpLeft > 1e-6; $i--) {
                $dateStr = $fullDays[$i];
                $w = $perDay;
                if ($lwpLeft + 1e-9 >= $w) {
                    if ($dateStr >= $musterStartStr && $dateStr <= $musterEndStr) {
                        $k = $empId.'|'.$dateStr;
                        $lop[$k] = round(($lop[$k] ?? 0) + $w, 4);
                    }
                    $lwpLeft -= $w;
                } else {
                    if ($lwpLeft > 1e-6 && $dateStr >= $musterStartStr && $dateStr <= $musterEndStr) {
                        $k = $empId.'|'.$dateStr;
                        $lop[$k] = round(($lop[$k] ?? 0) + $lwpLeft, 4);
                    }
                    $lwpLeft = 0;
                }
            }
        }

        return $lop;
    }

    public function index(Request $request)
    {
        if (Auth::user()->can('manage-attendance-records')) {
            $query = AttendanceRecord::with(['employee', 'shift', 'attendancePolicy', 'creator'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-attendance-records')) {
                        $q->whereIn('created_by', getCompanyAndUsersId());
                    } elseif (Auth::user()->can('manage-own-attendance-records')) {
                        $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Handle search
            if ($request->has('search') && ! empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('employee', function ($subQ) use ($request) {
                        $subQ->where('name', 'like', '%'.$request->search.'%');
                    });
                });
            }

            // Handle employee filter
            if ($request->has('employee_id') && ! empty($request->employee_id) && $request->employee_id !== 'all') {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle status filter
            if ($request->has('status') && ! empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle date range filter
            if ($request->has('date_from') && ! empty($request->date_from)) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && ! empty($request->date_to)) {
                $query->where('date', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && ! empty($request->sort_field)) {
                $sortField = $request->sort_field;
                $sortDirection = $request->sort_direction ?? 'asc';
                
                if ($sortField === 'date') {
                    $query->orderBy('date', $sortDirection);
                } else {
                    $query->orderBy('id', 'desc');
                }
            } else {
                $query->orderBy('id', 'desc');
            }

            $attendanceRecords = $query->paginate($request->per_page ?? 10);

            // Add leave type information for on_leave records and WFH marker.
            $attendanceRecords->getCollection()->transform(function ($record) {
                $record->is_wfh = false;

                if ($record->status === 'on_leave') {
                    $leaveApplication = LeaveApplication::where('employee_id', $record->employee_id)
                        ->whereDate('start_date', '<=', $record->date)
                        ->whereDate('end_date', '>=', $record->date)
                        ->where('status', 'approved')
                        ->with('leaveType')
                        ->first();

                    $record->leave_type = $leaveApplication ? $leaveApplication->leaveType : null;

                    $record->is_wfh = WorkFromHomeRequest::where('employee_id', $record->employee_id)
                        ->whereDate('start_date', '<=', $record->date)
                        ->whereDate('end_date', '>=', $record->date)
                        ->where('status', 'approved')
                        ->exists();
                }

                return $record;
            });

            // Get employees for filter dropdown
            $employees = User::where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->get(['id', 'name']);

            return Inertia::render('hr/attendance-records/index', [
                'attendanceRecords' => $attendanceRecords,
                'employees' => $this->getFilteredEmployees(),
                'hasSampleFile' => file_exists(storage_path('uploads/sample/sample-attendance-record.xlsx')),
                'filters' => $request->all(['search', 'employee_id', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-attendance-records') && ! Auth::user()->can('manage-any-attendance-records')) {
            $employeeQuery->where(function ($q) {
                $q->where('created_by', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        $employees = User::emp()
            ->with('employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee->employee_id ?? '',
                ];
            });

        return $employees;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'is_holiday' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Get employee with shift and policy
        $employee = Employee::where('user_id', $validated['employee_id'])->first();

        // Get working days from settings
        $globalSettings = settings();
        $workingDaysIndices = json_decode($globalSettings['working_days'] ?? '[]', true);

        if (empty($workingDaysIndices)) {
            return redirect()->back()->with('error', __('Please configure working days first.'));
        }

        $dateIndex = Carbon::parse($validated['date'])->dayOfWeek;
        if (! in_array($dateIndex, $workingDaysIndices)) {
            return redirect()->back()->with('error', __('Cannot create attendance record for non-working day.'));
        }

        if ($this->hasApprovedFullDayLeaveOnDate($validated['employee_id'], $validated['date'])) {
            return redirect()->back()->with('error', __('Employee has approved leave for this date. Cannot create attendance record.'));
        }

        // Check if record already exists
        $exists = AttendanceRecord::where('employee_id', $validated['employee_id'])
            ->where('date', $validated['date'])
            ->whereIn('created_by', getCompanyAndUsersId())
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', __('Attendance record already exists for this employee and date.'));
        }

        // Use employee's assigned shift and policy, or get defaults
        $shift = $employee && $employee->shift_id ?
            Shift::find($employee->shift_id) :
            Shift::whereIn('created_by', getCompanyAndUsersId())->where('status', 'active')->first();

        $policy = $employee && $employee->attendance_policy_id ?
            AttendancePolicy::find($employee->attendance_policy_id) :
            AttendancePolicy::whereIn('created_by', getCompanyAndUsersId())->where('status', 'active')->first();

        $validated['shift_id'] = $shift?->id;
        $validated['attendance_policy_id'] = $policy?->id;
        $validated['created_by'] = creatorId();
        $validated['is_holiday'] = $validated['is_holiday'] ?? false;
        $validated['break_hours'] = $validated['break_hours'] ?? 0;

        // Set weekend flag
        $validated['is_weekend'] = Carbon::parse($validated['date'])->isWeekend();

        $record = AttendanceRecord::create($validated);

        // Process complete attendance calculation
        $record->fresh(); // Reload to get relationships
        $record->processAttendance();

        return redirect()->back()->with('success', __('Attendance record created successfully.'));
    }

    public function update(Request $request, $attendanceRecordId)
    {

        $attendanceRecord = AttendanceRecord::where('id', $attendanceRecordId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        // Get working days from settings
        $globalSettings = settings();
        $workingDaysIndices = json_decode($globalSettings['working_days'] ?? '[]', true);

        if (empty($workingDaysIndices)) {
            return redirect()->back()->with('error', __('Please configure working days first.'));
        }

        $dateIndex = Carbon::parse($request->date)->dayOfWeek;
        if (! in_array($dateIndex, $workingDaysIndices)) {
            return redirect()->back()->with('error', __('Cannot create attendance record for non-working day.'));
        }

        if ($this->hasApprovedFullDayLeaveOnDate($request->employee_id, $request->date)) {
            return redirect()->back()->with('error', __('Employee has approved leave for this date. Cannot create attendance record.'));
        }

        if ($attendanceRecord) {
            try {
                $validated = $request->validate([
                    'employee_id' => 'required|exists:users,id',
                    'date' => 'required|date',
                    'clock_in' => 'nullable|date_format:H:i',
                    'clock_out' => 'nullable|date_format:H:i',
                    'break_hours' => 'nullable|numeric|min:0',
                    'is_holiday' => 'boolean',
                    'status' => 'required|in:present,absent,half_day,on_leave,holiday',
                    'notes' => 'nullable|string',
                ]);

                // Check if employee or date changed and if duplicate exists
                if ($attendanceRecord->employee_id != $validated['employee_id'] || $attendanceRecord->date != $validated['date']) {
                    $exists = AttendanceRecord::where('employee_id', $validated['employee_id'])
                        ->where('date', $validated['date'])
                        ->where('id', '!=', $attendanceRecordId)
                        ->exists();

                    if ($exists) {
                        return redirect()->back()->with('error', __('Attendance record already exists for this employee and date.'));
                    }
                }

                // Get employee with shift and policy
                $employee = \App\Models\Employee::where('user_id', $validated['employee_id'])->first();

                // Use employee's assigned shift and policy, or get defaults
                $shift = $employee && $employee->shift_id ?
                    Shift::find($employee->shift_id) :
                    Shift::whereIn('created_by', getCompanyAndUsersId())->where('status', 'active')->first();

                $policy = $employee && $employee->attendance_policy_id ?
                    AttendancePolicy::find($employee->attendance_policy_id) :
                    AttendancePolicy::whereIn('created_by', getCompanyAndUsersId())->where('status', 'active')->first();

                $validated['shift_id'] = $shift?->id;
                $validated['attendance_policy_id'] = $policy?->id;

                // Set weekend flag
                $validated['is_weekend'] = Carbon::parse($validated['date'])->isWeekend();

                $attendanceRecord->update($validated);

                // Process complete attendance calculation
                $attendanceRecord->fresh(); // Reload to get relationships
                $attendanceRecord->processAttendance();

                return redirect()->back()->with('success', __('Attendance record updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update attendance record'));
            }
        } else {
            return redirect()->back()->with('error', __('Attendance record Not Found.'));
        }
    }

    public function destroy($attendanceRecordId)
    {
        $attendanceRecord = AttendanceRecord::where('id', $attendanceRecordId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($attendanceRecord) {
            try {
                $attendanceRecord->delete();

                return redirect()->back()->with('success', __('Attendance record deleted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete attendance record'));
            }
        } else {
            return redirect()->back()->with('error', __('Attendance record Not Found.'));
        }
    }

    public function clockIn(Request $request)
    {
        if (Auth::user()->can('clock-in-out')) {
            try {
                $validated = $request->validate([
                    'employee_id' => 'required|exists:users,id',
                ]);

                $settings = settings();
                // Source - https://stackoverflow.com/a/55790
// Posted by Tim Kennedy, modified by community. See post 'Timeline' for change history
// Retrieved 2026-05-11, License - CC BY-SA 4.0
// added by Piyush on 11-05-2026 to check the user';'s IP
                if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else {
                    $ip = $_SERVER['REMOTE_ADDR'];
                }
                //echo $ip ; 
/************************************************************/
                if (! empty($settings['ipRestrictionEnabled']) && $settings['ipRestrictionEnabled'] == 1) {
                    //$loginUserIp = request()->ip();
                    $loginUserIp = $ip ;
                    //exit();
                    $ip = IpRestriction::whereIn('created_by', getCompanyAndUsersId())->where('ip_address', $loginUserIp)->first();
                    if (empty($ip) || is_null($ip)) {
                        return redirect()->back()->with('error', __('This IP Address Is Not Allowed For Clock In & Clock Out.'));
                    }
                }

                $today = Carbon::today();
                $now = Carbon::now();

                // Get working days from settings
                $globalSettings = settings();
                $workingDaysIndices = json_decode($globalSettings['working_days'] ?? '[]', true);

                if (empty($workingDaysIndices)) {
                    return redirect()->back()->with('error', __('Please configure working days first.'));
                }

                $dateIndex = Carbon::parse($today)->dayOfWeek;
                if (! in_array($dateIndex, $workingDaysIndices)) {
                    return redirect()->back()->with('error', __('Cannot create attendance record for non-working day.'));
                }

                if ($this->hasApprovedFullDayLeaveOnDate($validated['employee_id'], $today)) {
                    return redirect()->back()->with('error', __('Employee has approved leave for this date. Cannot create attendance record.'));
                }

                // Check if already clocked in today
                $existingRecord = AttendanceRecord::where('employee_id', $validated['employee_id'])
                    ->where('date', $today)
                    ->first();

                if ($existingRecord && $existingRecord->clock_in) {
                    return redirect()->back()->with('error', __('Already clocked in today.'));
                }

                // Get employee with shift and policy
                $employee = \App\Models\Employee::where('user_id', $validated['employee_id'])->first();

                if (! $employee) {
                    return redirect()->back()->with('error', __('Employee profile not found.'));
                }

                // Use employee's assigned shift and policy, or get defaults
                $shift = $employee->shift_id ?
                    Shift::find($employee->shift_id) :
                    Shift::whereIn('created_by', getCompanyAndUsersId())->where('status', 'active')->first();

                $policy = $employee->attendance_policy_id ?
                    AttendancePolicy::find($employee->attendance_policy_id) :
                    AttendancePolicy::whereIn('created_by', getCompanyAndUsersId())->where('status', 'active')->first();

                if (! $shift || ! $policy) {
                    return redirect()->back()->with('error', __('No active shift or attendance policy found. Please contact HR.'));
                }

                if ($existingRecord) {
                    $statusAfterClockIn = $existingRecord->status === 'half_day' ? 'half_day' : 'present';

                    $existingRecord->update([
                        'clock_in' => $now->format('H:i:s'),
                        'shift_id' => $shift->id,
                        'attendance_policy_id' => $policy->id,
                        'status' => $statusAfterClockIn,
                    ]);
                    $record = $existingRecord;
                } else {
                    $record = AttendanceRecord::create([
                        'employee_id' => $validated['employee_id'],
                        'date' => $today,
                        'clock_in' => $now->format('H:i:s'),
                        'shift_id' => $shift->id,
                        'attendance_policy_id' => $policy->id,
                        'is_weekend' => $today->isWeekend(),
                        'status' => 'present',
                        'created_by' => creatorId(),
                    ]);
                }

                // Check for late arrival if methods exist
                if (method_exists($record, 'checkLateArrival')) {
                    $record->checkLateArrival();
                    $record->save();
                }

                return redirect()->back()->with('success', __('Clocked in successfully.'));
            } catch (\Exception $e) {
                \Log::error('Clock in failed: '.$e->getMessage());

                return redirect()->back()->with('error', __('Failed to clock in. Please try again.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function clockOut(Request $request)
    {
        if (Auth::user()->can('clock-in-out')) {
            try {
                $validated = $request->validate([
                    'employee_id' => 'required|exists:users,id',
                ]);

                $today = Carbon::today();
                $now = Carbon::now();

                $record = AttendanceRecord::where('employee_id', $validated['employee_id'])
                    ->where('date', $today)
                    ->first();

                if (! $record || ! $record->clock_in) {
                    return redirect()->back()->with('error', __('Must clock in first.'));
                }

                if ($record->clock_out) {
                    return redirect()->back()->with('error', __('Already clocked out today.'));
                }

                $record->update([
                    'clock_out' => $now->format('H:i:s'),
                ]);

                // Process complete attendance calculation if method exists
                if (method_exists($record, 'processAttendance')) {
                    $record->processAttendance();
                }

                return redirect()->back()->with('success', __('Clocked out successfully.'));
            } catch (\Exception $e) {
                \Log::error('Clock out failed: '.$e->getMessage());

                return redirect()->back()->with('error', __('Failed to clock out. Please try again.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

    }

    public function getTodayAttendance(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
        ]);

        $today = Carbon::today();
        $attendance = AttendanceRecord::where('employee_id', $validated['employee_id'])
            ->where('date', $today)
            ->first();

        return Inertia::render('employee-dashboard', [
            'attendance' => $attendance,
        ]);
    }

    public function export()
    {
        if (Auth::user()->can('export-attendance-record')) {
            try {
                $attendanceRecords = AttendanceRecord::with(['employee', 'shift', 'attendancePolicy'])
                    ->where(function ($q) {
                        if (Auth::user()->can('manage-any-attendance-records')) {
                            $q->whereIn('created_by', getCompanyAndUsersId());
                        } elseif (Auth::user()->can('manage-own-attendance-records')) {
                            $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id());
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    })->orderBy('date', 'desc')->get();

                $fileName = 'attendance_records_'.date('Y-m-d_His').'.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                ];

                $callback = function () use ($attendanceRecords) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, [
                        'Employee',
                        'Date',
                        'Shift',
                        'Attedance Policy',
                        'Clock In',
                        'Clock Out',
                        'Break Hours',
                        'Total Hours',
                        'Overtime Hours',
                        'Status',
                        'Is Late',
                        'Is Early Departure',
                        'Notes'
                    ]);

                    foreach ($attendanceRecords as $record) {
                        fputcsv($file, [
                            $record->employee->name ?? '',
                            $record->date ? date('Y-m-d', strtotime($record->date)) : '',
                            $record->shift->name ?? '',
                            $record->attendancePolicy->name ?? '',
                            $record->clock_in ?? '',
                            $record->clock_out ?? '',
                            $record->break_hours ?? '',
                            $record->total_hours ?? '',
                            $record->overtime_hours ?? '',
                            $record->status ?? '',
                            $record->is_late ? 'Yes' : 'No',
                            $record->is_early_departure ? 'Yes' : 'No',
                            $record->notes ?? ''
                        ]);
                    }
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            } catch (\Exception $e) {
                return response()->json(['message' => __('Failed to export attendance records: :message', ['message' => $e->getMessage()])], 500);
            }
        } else {
            return response()->json(['message' => __('Permission Denied.')], 403);
        }
    }

    public function downloadTemplate()
    {
        $filePath = storage_path('uploads/sample/sample-attendance-record.xlsx');
        if (! file_exists($filePath)) {
            return response()->json(['error' => __('Template file not available')], 404);
        }

        return response()->download($filePath, 'sample-attendance-record.xlsx');
    }

    public function parseFile(Request $request)
    {
        if (Auth::user()->can('import-attendance-record')) {
            $rules = ['file' => 'required|mimes:csv,txt,xlsx,xls'];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->getMessageBag()->first()]);
            }

            try {
                $file = $request->file('file');
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                $worksheet = $spreadsheet->getActiveSheet();
                $highestColumn = $worksheet->getHighestColumn();
                $highestRow = $worksheet->getHighestRow();
                $headers = [];

                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $value = $worksheet->getCell($col.'1')->getValue();
                    if ($value) {
                        $headers[] = (string) $value;
                    }
                }

                $previewData = [];
                for ($row = 2; $row <= $highestRow; $row++) {
                    $rowData = [];
                    $colIndex = 0;
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        if ($colIndex < count($headers)) {
                            $rowData[$headers[$colIndex]] = (string) $worksheet->getCell($col.$row)->getValue();
                        }
                        $colIndex++;
                    }
                    $previewData[] = $rowData;
                }

                return response()->json(['excelColumns' => $headers, 'previewData' => $previewData]);
            } catch (\Exception $e) {
                return response()->json(['message' => __('Failed to parse file: :error', ['error' => $e->getMessage()])]);
            }
        } else {
            return response()->json(['message' => __('Permission denied.')], 403);
        }
    }

    public function fileImport(Request $request)
    {
        if (Auth::user()->can('import-attendance-record')) {
            $rules = ['data' => 'required|array'];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->getMessageBag()->first());
            }

            try {
                $data = $request->data;
                $imported = 0;
                $skipped = 0;

                foreach ($data as $row) {
                    try {
                        if (empty($row['employee']) || empty($row['date'])) {
                            $skipped++;
                            continue;
                        }

                        $employee = User::where('name', $row['employee'])
                            ->whereIn('created_by', getCompanyAndUsersId())
                            ->where('type', 'employee')
                            ->first();

                        if (! $employee) {
                            $skipped++;
                            continue;
                        }

                        // Check if attendance record already exists for this employee and date
                        $exists = AttendanceRecord::where('employee_id', $employee->id)
                            ->whereDate('date', $row['date'])
                            ->exists();

                        if ($exists) {
                            $skipped++;
                            continue;
                        }

                        // Get employee with shift and policy
                        $employeeModel = Employee::where('user_id', $employee->id)->first();

                        $shift = $employeeModel && $employeeModel->shift_id ?
                            Shift::find($employeeModel->shift_id) :
                            Shift::whereIn('created_by', getCompanyAndUsersId())->where('status', 'active')->first();

                        $policy = $employeeModel && $employeeModel->attendance_policy_id ?
                            AttendancePolicy::find($employeeModel->attendance_policy_id) :
                            AttendancePolicy::whereIn('created_by', getCompanyAndUsersId())->where('status', 'active')->first();

                        if (! $shift || ! $policy) {
                            $skipped++;
                            continue;
                        }

                        $record = AttendanceRecord::create([
                            'employee_id' => $employee->id,
                            'date' => $row['date'],
                            'shift_id' => $shift->id,
                            'attendance_policy_id' => $policy->id,
                            'clock_in' => $row['clock_in'] ?? null,
                            'clock_out' => $row['clock_out'] ?? null,
                            'created_by' => creatorId(),
                        ]);

                        // Process attendance calculation
                        if (method_exists($record, 'processAttendance')) {
                            $record->processAttendance();
                        }
                        $imported++;
                    } catch (\Exception $e) {
                        $skipped++;
                    }
                }

                return redirect()->back()->with('success', __('Import completed: :added attendance records added, :skipped attendance records skipped', ['added' => $imported, 'skipped' => $skipped]));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to import: :error', ['error' => $e->getMessage()]));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Whether the employee has approved leave on this calendar date that is not solely a half-day (0.5 day) request.
     * Half-day leave still expects the employee to work (and clock) for the remaining part of the day.
     */
    private function hasApprovedFullDayLeaveOnDate($employeeId, $date): bool
    {
        return LeaveApplication::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get()
            ->contains(function ($leave) {
                return abs((float) $leave->total_days - 0.5) > 0.00001;
            });
    }

    private function isBackdatedEmployeeRequestAllowed(): bool
    {
        $value = settings()['allowBackdatedAttendanceRequests'] ?? true;
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function getClosedAttendanceMonths(): array
    {
        $raw = settings()['closedAttendanceMonths'] ?? '[]';
        if (is_array($raw)) {
            return collect($raw)->mapWithKeys(fn ($value) => [(string) $value => true])->all();
        }

        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)->mapWithKeys(fn ($value) => [(string) $value => true])->all();
    }

    private function isMonthManuallyClosed(string $monthKey): bool
    {
        $months = $this->getClosedAttendanceMonths();
        return isset($months[$monthKey]);
    }
}
