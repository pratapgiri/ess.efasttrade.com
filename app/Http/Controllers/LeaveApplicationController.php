<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\HrEventNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveApplicationController extends Controller
{
    private HrEventNotificationService $hrNotifier;

    public function __construct(HrEventNotificationService $hrNotifier)
    {
        $this->hrNotifier = $hrNotifier;
    }

    public function index(Request $request)
    {
        if (Auth::user()->can('manage-leave-applications')) {
            $query = LeaveApplication::with(['employee', 'leaveType', 'leavePolicy', 'approver', 'creator'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-leave-applications')) {
                        $q->whereIn('created_by', getCompanyAndUsersId());
                    } elseif (Auth::user()->can('manage-own-leave-applications')) {
                        $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id())->orWhere('approved_by', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Handle search
            if ($request->has('search') && ! empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('reason', 'like', '%'.$request->search.'%')
                        ->orWhereHas('employee', function ($subQ) use ($request) {
                            $subQ->where('name', 'like', '%'.$request->search.'%');
                        })
                        ->orWhereHas('leaveType', function ($subQ) use ($request) {
                            $subQ->where('name', 'like', '%'.$request->search.'%');
                        });
                });
            }

            // Handle employee filter
            if ($request->has('employee_id') && ! empty($request->employee_id) && $request->employee_id !== 'all') {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle leave type filter
            if ($request->has('leave_type_id') && ! empty($request->leave_type_id) && $request->leave_type_id !== 'all') {
                $query->where('leave_type_id', $request->leave_type_id);
            }

            // Handle status filter
            if ($request->has('status') && ! empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle sorting
            if ($request->has('sort_field') && ! empty($request->sort_field)) {
                $sortField = $request->sort_field;
                $sortDirection = $request->sort_direction ?? 'asc';

                if (in_array($sortField, ['start_date', 'end_date', 'created_at'])) {
                    $query->orderBy($sortField, $sortDirection);
                } else {
                    $query->orderBy('id', 'desc');
                }
            } else {
                $query->orderBy('id', 'desc');
            }

            $leaveApplications = $query->paginate($request->per_page ?? 10);

            // Get employees for filter dropdown
            $employees = User::where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->get(['id', 'name']);

            // Get leave types for filter dropdown
            $leaveTypes = LeaveType::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->get(['id', 'name', 'color']);

            return Inertia::render('hr/leave-applications/index', [
                'leaveApplications' => $leaveApplications,
                'employees' => $this->getFilteredEmployees(),
                'leaveTypes' => $leaveTypes,
                'filters' => $request->all(['search', 'employee_id', 'leave_type_id', 'status', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-leave-applications') && ! Auth::user()->can('manage-any-leave-applications')) {
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
        $isManageAny = Auth::user()->can('manage-any-leave-applications');
        $allowBackdated = $this->isBackdatedEmployeeRequestAllowed();
        $startDateRule = $isManageAny || $allowBackdated
            ? 'required|date'
            : 'required|date|after_or_equal:today';

        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => $startDateRule,
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_half_day' => 'nullable|boolean',
            'half_day_part' => 'nullable|in:first_half,second_half',
            'reason' => 'required|string',
            'attachment' => 'nullable|string',
        ]);

        $validated['created_by'] = creatorId();
        if (! $isManageAny && (int) $validated['employee_id'] !== (int) Auth::id()) {
            return redirect()->back()->with('error', __('You can only create leave requests for yourself.'));
        }

        if ($this->hasClosedPayrollOverlap($validated['start_date'], $validated['end_date'])) {
            return redirect()->back()->with('error', __('Selected dates include a closed payroll month. Leave request is not allowed.'));
        }

        // Calculate total days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $isHalfDay = (bool) ($validated['is_half_day'] ?? false);
        if ($isHalfDay) {
            if (! $startDate->isSameDay($endDate)) {
                return redirect()->back()->with('error', __('For half day leave, start date and end date must be the same.'));
            }
            if (empty($validated['half_day_part'])) {
                return redirect()->back()->with('error', __('Please select whether this half day leave is for the first half or second half of the day.'));
            }
            $validated['total_days'] = 0.5;
        } else {
            $validated['total_days'] = $startDate->diffInDays($endDate) + 1;
            $validated['half_day_part'] = null;
        }

        unset($validated['is_half_day']);

        // Get leave policy for this leave type
        $leavePolicy = LeavePolicy::where('leave_type_id', $validated['leave_type_id'])
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->first();

        if (! $leavePolicy) {
            return redirect()->back()->with('error', __('No active policy found for selected leave type.'));
        }

        $validated['leave_policy_id'] = $leavePolicy->id;

        // Validate days per application.
        // Half-day leave is allowed as a special case even if policy min is 1.
        $isHalfDay = (float) $validated['total_days'] === 0.5;
        $isBelowMin = $validated['total_days'] < $leavePolicy->min_days_per_application;
        $isAboveMax = $validated['total_days'] > $leavePolicy->max_days_per_application;
        if (($isBelowMin && ! $isHalfDay) || $isAboveMax) {
            return redirect()->back()->with(
                'error',
                __('Leave days must be between :min and :max days.', [
                    'min' => $leavePolicy->min_days_per_application,
                    'max' => $leavePolicy->max_days_per_application,
                ])
            );
        }

        // Check if employee has enough leave balance
        $currentYear = now()->year;
        $leaveBalance = \App\Models\LeaveBalance::where('employee_id', $validated['employee_id'])
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $currentYear)
            ->first();

        if (! $leaveBalance) {
            // Create initial balance if doesn't exist
            $leaveBalance = \App\Models\LeaveBalance::create([
                'employee_id' => $validated['employee_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'leave_policy_id' => $leavePolicy->id,
                'year' => $currentYear,
                'allocated_days' => $leavePolicy->max_days_per_year ?? 10,
                'used_days' => 0,
                'remaining_days' => $leavePolicy->max_days_per_year ?? 10,
                'created_by' => creatorId(),
            ]);
        }

        // Allow applying above available balance.
        // Negative balance will be treated as LWP during approval/payroll.

        // Handle attachment from media library
        if ($request->has('attachment')) {
            $validated['attachment'] = $request->attachment;
        }

        // Set status based on policy
        $validated['status'] = $leavePolicy->requires_approval ? 'pending' : 'approved';

        $leaveApplication = LeaveApplication::create($validated);

        // Create attendance records if auto-approved
        if ($validated['status'] === 'approved') {
            $leaveApplication->createAttendanceRecords();
        }

        try {
            $companyId = (int) ($leaveApplication->created_by ?: creatorId());
            $approvers = $this->hrNotifier->getApproversByPermission($companyId, 'approve-leave-applications');
            $this->hrNotifier->notifyEvent(
                'leave_apply',
                $companyId,
                [
                    '{app_name}' => config('app.name', 'HRMS'),
                    '{employee_name}' => $leaveApplication->employee?->name ?? '',
                    '{start_date}' => (string) $leaveApplication->start_date?->format('Y-m-d'),
                    '{end_date}' => (string) $leaveApplication->end_date?->format('Y-m-d'),
                    '{total_days}' => (string) $leaveApplication->total_days,
                    '{reason}' => (string) ($leaveApplication->reason ?? ''),
                    '{status}' => (string) ($leaveApplication->status ?? 'pending'),
                    '{manager_comments}' => (string) ($leaveApplication->manager_comments ?? '-'),
                    '{date}' => (string) $leaveApplication->start_date?->format('Y-m-d'),
                    '{requested_clock_in}' => '-',
                    '{requested_clock_out}' => '-',
                ],
                $approvers,
                [
                    'entity' => 'leave_application',
                    'entity_id' => $leaveApplication->id,
                ]
            );
        } catch (\Throwable $e) {
            \Log::warning('Leave apply notification failed: '.$e->getMessage());
        }

        return redirect()->back()->with('success', __('Leave application created successfully.'));
    }

    private function hasClosedPayrollOverlap($startDate, $endDate): bool
    {
        $hasClosedPayrollRun = PayrollRun::query()
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'completed')
            ->whereDate('pay_period_start', '<=', Carbon::parse($endDate)->toDateString())
            ->whereDate('pay_period_end', '>=', Carbon::parse($startDate)->toDateString())
            ->exists();

        if ($hasClosedPayrollRun) {
            return true;
        }

        $raw = settings()['closedAttendanceMonths'] ?? '[]';
        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        $closedMonths = is_array($decoded) ? $decoded : [];
        if (empty($closedMonths)) {
            return false;
        }

        $cursor = Carbon::parse($startDate)->startOfMonth();
        $end = Carbon::parse($endDate)->endOfMonth();
        while ($cursor->lte($end)) {
            if (in_array($cursor->format('Y-m'), $closedMonths, true)) {
                return true;
            }
            $cursor->addMonth();
        }

        return false;
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

    public function update(Request $request, $leaveApplicationId)
    {
        $leaveApplication = LeaveApplication::where('id', $leaveApplicationId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveApplication) {
            try {
                $validated = $request->validate([
                    'employee_id' => 'required|exists:users,id',
                    'leave_type_id' => 'required|exists:leave_types,id',
                    'start_date' => 'required|date',
                    'end_date' => 'required|date|after_or_equal:start_date',
                    'is_half_day' => 'nullable|boolean',
                    'half_day_part' => 'nullable|in:first_half,second_half',
                    'reason' => 'required|string',
                    'attachment' => 'nullable|string',
                ]);

                // Calculate total days
                $startDate = Carbon::parse($validated['start_date']);
                $endDate = Carbon::parse($validated['end_date']);
                $isHalfDay = (bool) ($validated['is_half_day'] ?? false);
                if ($isHalfDay) {
                    if (! $startDate->isSameDay($endDate)) {
                        return redirect()->back()->with('error', __('For half day leave, start date and end date must be the same.'));
                    }
                    if (empty($validated['half_day_part'])) {
                        return redirect()->back()->with('error', __('Please select whether this half day leave is for the first half or second half of the day.'));
                    }
                    $validated['total_days'] = 0.5;
                } else {
                    $validated['total_days'] = $startDate->diffInDays($endDate) + 1;
                    $validated['half_day_part'] = null;
                }

                unset($validated['is_half_day']);

                // Get leave policy
                $leavePolicy = LeavePolicy::where('leave_type_id', $validated['leave_type_id'])
                    ->whereIn('created_by', getCompanyAndUsersId())
                    ->where('status', 'active')
                    ->first();

                if (! $leavePolicy) {
                    return redirect()->back()->with('error', __('No active policy found for selected leave type.'));
                }

                $validated['leave_policy_id'] = $leavePolicy->id;

                // Handle attachment from media library
                if ($request->has('attachment')) {
                    $validated['attachment'] = $request->attachment;
                }

                // If this leave is approved and already deducted, the days/type/employee
                // change must be reflected in used_days. Safest path: revert the prior
                // deduction with the snapshot, persist the new values, then re-apply.
                $wasApprovedAndDeducted = ($leaveApplication->status === 'approved')
                    && ($leaveApplication->balance_deducted_at !== null);

                if ($wasApprovedAndDeducted) {
                    $leaveApplication->revertFromBalance();
                }

                $leaveApplication->update($validated);

                if ($wasApprovedAndDeducted) {
                    // Refresh relations so applyToBalance sees the new policy/type/etc.
                    $leaveApplication->refresh()->load('leavePolicy');
                    $leaveApplication->applyToBalance();
                }

                return redirect()->back()->with('success', __('Leave application updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update leave application'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave application Not Found.'));
        }
    }

    public function destroy($leaveApplicationId)
    {
        $leaveApplication = LeaveApplication::where('id', $leaveApplicationId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveApplication) {
            try {
                // Restore used_days before removing the row, so the balance reflects
                // reality after the delete.
                if ($leaveApplication->balance_deducted_at !== null) {
                    $leaveApplication->revertFromBalance();
                }

                $leaveApplication->delete();

                return redirect()->back()->with('success', __('Leave application deleted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete leave application'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave application Not Found.'));
        }
    }

    public function updateStatus(Request $request, $leaveApplicationId)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'manager_comments' => 'nullable|string',
        ]);

        $leaveApplication = LeaveApplication::where('id', $leaveApplicationId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($leaveApplication) {
            try {
                $previousStatus = $leaveApplication->status;

                $leaveApplication->update([
                    'status' => $validated['status'],
                    'manager_comments' => $validated['manager_comments'],
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                // Status transitions:
                //   pending/rejected -> approved : apply (idempotent if already deducted).
                //   approved        -> rejected : revert previous deduction.
                //   approved        -> approved : applyToBalance no-ops via stamp.
                if ($validated['status'] === 'approved') {
                    $leaveApplication->createAttendanceRecords();
                } elseif ($validated['status'] === 'rejected' && $previousStatus === 'approved') {
                    $leaveApplication->revertFromBalance();
                }

                try {
                    $companyId = (int) ($leaveApplication->created_by ?: creatorId());
                    $employee = User::find($leaveApplication->employee_id);
                    $recipients = collect($employee ? [$employee] : [])->filter(fn ($u) => ! empty($u->email));
                    $this->hrNotifier->notifyEvent(
                        'leave_status',
                        $companyId,
                        [
                            '{app_name}' => config('app.name', 'HRMS'),
                            '{employee_name}' => $employee->name ?? '',
                            '{start_date}' => (string) $leaveApplication->start_date?->format('Y-m-d'),
                            '{end_date}' => (string) $leaveApplication->end_date?->format('Y-m-d'),
                            '{total_days}' => (string) $leaveApplication->total_days,
                            '{reason}' => (string) ($leaveApplication->reason ?? ''),
                            '{status}' => ucfirst((string) $validated['status']),
                            '{manager_comments}' => (string) ($validated['manager_comments'] ?? '-'),
                            '{date}' => (string) $leaveApplication->start_date?->format('Y-m-d'),
                            '{requested_clock_in}' => '-',
                            '{requested_clock_out}' => '-',
                        ],
                        $recipients,
                        [
                            'entity' => 'leave_application',
                            'entity_id' => $leaveApplication->id,
                        ]
                    );
                } catch (\Throwable $e) {
                    \Log::warning('Leave status notification failed: '.$e->getMessage());
                }

                return redirect()->back()->with('success', __('Leave application status updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update leave application status'));
            }
        } else {
            return redirect()->back()->with('error', __('Leave application Not Found.'));
        }
    }

    public function export()
    {
        if (Auth::user()->can('export-leave-applications')) {
            try {
                $leaveApplications = LeaveApplication::with(['employee', 'leaveType', 'approver'])
                    ->where(function ($q) {
                        if (Auth::user()->can('manage-any-leave-applications')) {
                            $q->whereIn('created_by', getCompanyAndUsersId());
                        } elseif (Auth::user()->can('manage-own-leave-applications')) {
                            $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id())->orWhere('approved_by', Auth::id());
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    })->get();

                $fileName = 'leave_applications_'.date('Y-m-d_His').'.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                ];

                $callback = function () use ($leaveApplications) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, [
                        'Employee',
                        'Leave Type',
                        'Start Date',
                        'End Date',
                        'Total Days',
                        'Half Day Part',
                        'Reason',
                        'Status',
                        'Approved By',
                        'Approved At',
                        'Manager Comments',
                        'Applied On',
                    ]);

                    foreach ($leaveApplications as $application) {
                        $halfPart = $application->half_day_part === 'second_half'
                            ? 'Second half'
                            : ($application->half_day_part === 'first_half' ? 'First half' : '');
                        fputcsv($file, [
                            $application->employee->name ?? '',
                            $application->leaveType->name ?? '',
                            $application->start_date ? date('Y-m-d', strtotime($application->start_date)) : '',
                            $application->end_date ? date('Y-m-d', strtotime($application->end_date)) : '',
                            $application->total_days ?? '',
                            $halfPart,
                            $application->reason ?? '',
                            $application->status ?? '',
                            $application->approver->name ?? '',
                            $application->approved_at ?? '',
                            $application->manager_comments ?? '',
                            $application->created_at ?? '',
                        ]);
                    }
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            } catch (\Exception $e) {
                return response()->json(['message' => __('Failed to export leave applications: :message', ['message' => $e->getMessage()])], 500);
            }
        } else {
            return response()->json(['message' => __('Permission Denied.')], 403);
        }
    }
}
