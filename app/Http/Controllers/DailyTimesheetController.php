<?php

namespace App\Http\Controllers;

use App\Models\DailyTimesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DailyTimesheetController extends Controller
{
    public function form(Request $request)
    {
        $authUser = Auth::user();
        $canManageOwn = $authUser->can('manage-own-time-entries');
        $canApprove = $authUser->can('approve-time-entries');
        if (! $canManageOwn && ! $canApprove) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $selectedDate = $request->get('date', now()->toDateString());
        $timesheetId = $request->get('timesheet_id');

        $query = DailyTimesheet::query()->with('items')->whereIn('created_by', getCompanyAndUsersId());

        if ($timesheetId) {
            $query->where('id', $timesheetId);
        } else {
            if (! $canManageOwn) {
                return redirect()->back()->with('error', __('Timesheet ID is required.'));
            }
            $query->whereDate('date', $selectedDate);
        }

        if (! $canApprove) {
            $query->where('employee_id', $authUser->id);
        }

        $timesheet = $query->first();

        if ($timesheetId && ! $timesheet) {
            return redirect()->back()->with('error', __('Timesheet not found.'));
        }

        return Inertia::render('hr/daily-timesheets/form', [
            'employees' => [[
                'id' => $authUser->id,
                'name' => $authUser->name,
            ]],
            'timesheet' => $timesheet ? [
                'id' => $timesheet->id,
                'employee_id' => $timesheet->employee_id,
                'date' => Carbon::parse($timesheet->date)->toDateString(),
                'work_mode' => $timesheet->work_mode,
                'overall_note' => $timesheet->overall_note,
                'status' => $timesheet->status,
                'items' => $timesheet->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'line_no' => $item->line_no,
                        'from_time' => substr((string) $item->from_time, 0, 5),
                        'to_time' => substr((string) $item->to_time, 0, 5),
                        'task_note' => $item->task_note,
                    ];
                })->values(),
            ] : null,
            'filters' => [
                'date' => $selectedDate,
            ],
            'isReadOnly' => $canApprove,
        ]);
    }

    public function myIndex(Request $request)
    {
        if (! Auth::user()->can('manage-own-time-entries')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $authUser = Auth::user();
        $query = DailyTimesheet::query()
            ->where('employee_id', $authUser->id)
            ->whereIn('created_by', getCompanyAndUsersId());

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $timesheets = $query->orderByDesc('date')->paginate($request->per_page ?? 10);

        return Inertia::render('hr/daily-timesheets/index', [
            'timesheets' => $timesheets,
            'filters' => $request->all(['status', 'date_from', 'date_to', 'per_page']),
            'mode' => 'employee',
        ]);
    }

    public function approvalsIndex(Request $request)
    {
        if (! Auth::user()->can('approve-time-entries')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $query = DailyTimesheet::query()
            ->with(['employee', 'items'])
            ->whereIn('created_by', getCompanyAndUsersId());

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id') && $request->employee_id !== 'all') {
            $query->where('employee_id', $request->employee_id);
        }

        $timesheets = $query->orderByDesc('date')->paginate($request->per_page ?? 10);
        $employees = User::query()
            ->where('type', 'employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('hr/daily-timesheets/index', [
            'timesheets' => $timesheets,
            'employees' => $employees,
            'filters' => $request->all(['status', 'employee_id', 'per_page']),
            'mode' => 'approval',
        ]);
    }

    public function saveDraft(Request $request)
    {
        if (! Auth::user()->can('manage-own-time-entries')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $data = $this->validatePayload($request);
        $authUser = Auth::user();
        if ((int) $data['employee_id'] !== (int) $authUser->id) {
            return redirect()->back()->with('error', __('You can only submit your own timesheet.'));
        }

        try {
            $timesheet = $this->upsertTimesheet($data, 'draft');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('hr.daily-timesheets.form', [
            'timesheet_id' => $timesheet->id,
        ])->with('success', __('Timesheet saved as draft.'));
    }

    public function submitForApproval(Request $request)
    {
        if (! Auth::user()->can('manage-own-time-entries')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $data = $this->validatePayload($request);
        $authUser = Auth::user();
        if ((int) $data['employee_id'] !== (int) $authUser->id) {
            return redirect()->back()->with('error', __('You can only submit your own timesheet.'));
        }

        try {
            $this->upsertTimesheet($data, 'pending');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('hr.daily-timesheets.my.index')->with('success', __('Timesheet submitted for approval.'));
    }

    public function updateStatus(Request $request, $timesheetId)
    {
        if (! Auth::user()->can('approve-time-entries')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'manager_comments' => 'nullable|string',
        ]);

        $timesheet = DailyTimesheet::query()
            ->where('id', $timesheetId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if (! $timesheet) {
            return redirect()->back()->with('error', __('Timesheet not found.'));
        }

        if ($timesheet->status !== 'pending') {
            return redirect()->back()->with('error', __('Only pending timesheets can be approved or rejected.'));
        }

        $timesheet->update([
            'status' => $validated['status'],
            'manager_comments' => $validated['manager_comments'] ?? null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', __('Timesheet status updated successfully.'));
    }

    public function destroy($timesheetId)
    {
        $authUser = Auth::user();
        $canManageOwn = $authUser->can('manage-own-time-entries');
        $canApprove = $authUser->can('approve-time-entries');
        if (! $canManageOwn && ! $canApprove) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $query = DailyTimesheet::query()
            ->where('id', $timesheetId)
            ->whereIn('created_by', getCompanyAndUsersId());

        if (! $canApprove) {
            $query->where('employee_id', $authUser->id);
        }

        $timesheet = $query->first();
        if (! $timesheet) {
            return redirect()->back()->with('error', __('Timesheet not found.'));
        }

        if ($timesheet->status !== 'pending') {
            return redirect()->back()->with('error', __('Only pending timesheets can be deleted.'));
        }

        $timesheet->delete();

        return redirect()->back()->with('success', __('Timesheet deleted successfully.'));
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'work_mode' => 'required|in:office,home,hybrid',
            'overall_note' => 'nullable|string',
            'line_items' => 'required|array|min:1',
            'line_items.*.from_time' => 'required|date_format:H:i',
            'line_items.*.to_time' => 'required|date_format:H:i',
            'line_items.*.task_note' => 'nullable|string',
        ]);
    }

    private function upsertTimesheet(array $data, string $status): DailyTimesheet
    {
        return DB::transaction(function () use ($data, $status) {
            $timesheet = DailyTimesheet::query()
                ->where('employee_id', $data['employee_id'])
                ->whereDate('date', $data['date'])
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if (! $timesheet) {
                $timesheet = DailyTimesheet::create([
                    'employee_id' => $data['employee_id'],
                    'date' => $data['date'],
                    'work_mode' => $data['work_mode'],
                    'overall_note' => $data['overall_note'] ?? null,
                    'status' => $status,
                    'submitted_at' => $status === 'pending' ? now() : null,
                    'created_by' => creatorId(),
                ]);
            } else {
                if ($timesheet->status === 'approved') {
                    throw new \RuntimeException(__('Approved timesheet cannot be modified.'));
                }

                $timesheet->update([
                    'work_mode' => $data['work_mode'],
                    'overall_note' => $data['overall_note'] ?? null,
                    'status' => $status,
                    'submitted_at' => $status === 'pending' ? now() : null,
                    'approved_by' => null,
                    'approved_at' => null,
                    'manager_comments' => null,
                ]);
            }

            $timesheet->items()->delete();
            foreach ($data['line_items'] as $index => $item) {
                $timesheet->items()->create([
                    'line_no' => $index + 1,
                    'from_time' => $item['from_time'],
                    'to_time' => $item['to_time'],
                    'task_note' => $item['task_note'] ?? null,
                ]);
            }

            return $timesheet->fresh('items');
        });
    }
}

