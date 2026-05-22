<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use App\Services\HrEventNotificationService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WorkFromHomeRequestController extends Controller
{
    private ?HrEventNotificationService $hrNotifier;

    public function __construct()
    {
        try {
            $this->hrNotifier = app(HrEventNotificationService::class);
        } catch (BindingResolutionException $e) {
            $this->hrNotifier = null;
            \Log::warning('HrEventNotificationService is unavailable. WFH notifications are skipped.');
        }
    }

    private function normalizeAttachmentPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalized = str_replace('\\', '/', trim($path));
        if ($normalized === '') {
            return null;
        }

        if (Str::startsWith($normalized, ['http://', 'https://']) && str_contains($normalized, '/storage/')) {
            $normalized = explode('/storage/', $normalized, 2)[1] ?? $normalized;
        }

        $normalized = ltrim($normalized, '/');
        $normalized = preg_replace('#^app/public/#', '', $normalized);
        $normalized = preg_replace('#^public/#', '', $normalized);
        $normalized = preg_replace('#^storage/#', '', $normalized);

        if (str_contains($normalized, 'media/')) {
            $mediaPos = strpos($normalized, 'media/');
            $normalized = substr($normalized, $mediaPos);
        }

        return $normalized;
    }

    private function resolveAttachmentUrl(?string $path): ?string
    {
        $normalized = $this->normalizeAttachmentPath($path);
        if (! $normalized) {
            return null;
        }

        return asset('storage/'.$normalized);
    }

    private function canOpenListing(): bool
    {
        return Auth::user()->can('manage-wfh-applications')
            || Auth::user()->can('manage-own-wfh-applications')
            || Auth::user()->can('view-wfh-applications')
            || Auth::user()->can('create-wfh-applications');
    }

    private function canManageAny(): bool
    {
        return Auth::user()->can('manage-any-wfh-applications')
            || Auth::user()->can('manage-wfh-applications');
    }

    private function isScopedRequest(WorkFromHomeRequest $request): bool
    {
        if (! in_array($request->created_by, getCompanyAndUsersId())) {
            return false;
        }

        if ($this->canManageAny()) {
            return true;
        }

        return (int) $request->employee_id === (int) Auth::id()
            || (int) $request->created_by === (int) Auth::id()
            || (int) $request->approved_by === (int) Auth::id();
    }

    public function index(Request $request)
    {
        if (! $this->canOpenListing()) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $query = WorkFromHomeRequest::with(['employee.employee.department', 'employee.employee.designation', 'approver'])
            ->where(function ($q) {
                if (Auth::user()->can('manage-any-wfh-applications')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-wfh-applications')) {
                    $q->where('created_by', Auth::id())
                        ->orWhere('employee_id', Auth::id())
                        ->orWhere('approved_by', Auth::id());
                } elseif (Auth::user()->can('view-wfh-applications') || Auth::user()->can('create-wfh-applications')) {
                    $q->where('employee_id', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('reason', 'like', '%'.$request->search.'%')
                    ->orWhereHas('employee', function ($subQ) use ($request) {
                        $subQ->where('name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        if ($request->filled('employee_id') && $request->employee_id !== 'all') {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('sort_field') && in_array($request->sort_field, ['start_date', 'end_date', 'created_at'], true)) {
            $query->orderBy($request->sort_field, $request->get('sort_direction', 'asc'));
        } else {
            $query->orderByDesc('id');
        }

        $requests = $query->paginate($request->per_page ?? 10);
        $requests->getCollection()->transform(function ($requestItem) {
            $requestItem->attachment = $this->normalizeAttachmentPath($requestItem->attachment);
            $requestItem->attachment_url = $this->resolveAttachmentUrl($requestItem->attachment);

            return $requestItem;
        });

        $employeesQuery = User::emp()
            ->with('employee.department:id,name', 'employee.designation:id,name')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->select('id', 'name');

        if (! Auth::user()->can('manage-any-wfh-applications')) {
            $employeesQuery->where('id', Auth::id());
        }

        $employees = $employeesQuery
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee->employee_id ?? '',
                    'department' => optional(optional($user->employee)->department)->name,
                    'designation' => optional(optional($user->employee)->designation)->name,
                ];
            })
            ->values();

        return Inertia::render('hr/work-from-home-requests/index', [
            'wfhRequests' => $requests,
            'employees' => $employees,
            'filters' => $request->all(['search', 'employee_id', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function show(WorkFromHomeRequest $workFromHomeRequest)
    {
        if (! $this->canOpenListing() || ! $this->isScopedRequest($workFromHomeRequest)) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $workFromHomeRequest->load(['employee.employee.department', 'employee.employee.designation', 'approver', 'creator']);

        return Inertia::render('hr/work-from-home-requests/show', [
            'wfhRequest' => $workFromHomeRequest,
        ]);
    }

    public function store(Request $request)
    {
        if (! Auth::user()->can('create-wfh-applications')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $startDateRules = $this->canManageAny()
            ? 'required|date'
            : 'required|date|after_or_equal:today';

        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'start_date' => $startDateRules,
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'nullable|string',
            'designation' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
        ]);

        if (! Auth::user()->can('manage-any-wfh-applications')) {
            $validated['employee_id'] = Auth::id();
        }

        $employee = Employee::with(['department:id,name', 'designation:id,name'])
            ->where('user_id', $validated['employee_id'])
            ->first();

        $validated['designation'] = optional(optional($employee)->designation)->name ?? $validated['designation'] ?? null;
        $validated['department'] = optional(optional($employee)->department)->name ?? $validated['department'] ?? null;
        $validated['attachment'] = $this->normalizeAttachmentPath($validated['attachment'] ?? null);
        $validated['status'] = 'pending';
        $validated['created_by'] = creatorId();

        $wfhRequest = WorkFromHomeRequest::create($validated);

        if ($this->hrNotifier) {
            try {
            $companyId = (int) ($wfhRequest->created_by ?: creatorId());
            $approvers = $this->hrNotifier->getApproversByPermission($companyId, 'approve-wfh-applications');
            $this->hrNotifier->notifyEvent(
                'wfh_apply',
                $companyId,
                [
                    '{app_name}' => config('app.name', 'HRMS'),
                    '{employee_name}' => optional($wfhRequest->employee)->name ?? '',
                    '{start_date}' => (string) optional($wfhRequest->start_date)->format('Y-m-d'),
                    '{end_date}' => (string) optional($wfhRequest->end_date)->format('Y-m-d'),
                    '{total_days}' => (string) ((optional($wfhRequest->start_date)->diffInDays($wfhRequest->end_date) ?? 0) + 1),
                    '{reason}' => (string) ($wfhRequest->reason ?? ''),
                    '{status}' => (string) ($wfhRequest->status ?? 'pending'),
                    '{manager_comments}' => (string) ($wfhRequest->manager_comments ?? '-'),
                    '{date}' => (string) optional($wfhRequest->start_date)->format('Y-m-d'),
                    '{requested_clock_in}' => '-',
                    '{requested_clock_out}' => '-',
                ],
                $approvers,
                [
                    'entity' => 'wfh_request',
                    'entity_id' => $wfhRequest->id,
                ]
            );
            } catch (\Throwable $e) {
                \Log::warning('WFH apply notification failed: '.$e->getMessage());
            }
        }

        return redirect()->back()->with('success', __('WFH request submitted successfully.'));
    }

    public function update(Request $request, WorkFromHomeRequest $workFromHomeRequest)
    {
        if (! Auth::user()->can('edit-wfh-applications')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        if (! $this->isScopedRequest($workFromHomeRequest)) {
            return redirect()->back()->with('error', __('WFH request not found.'));
        }

        if ($workFromHomeRequest->status !== 'pending') {
            return redirect()->back()->with('error', __('Only pending WFH requests can be edited.'));
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment' => 'nullable|string',
            'designation' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
        ]);

        if (! $this->canManageAny()) {
            $validated['employee_id'] = Auth::id();
        }

        $employee = Employee::with(['department:id,name', 'designation:id,name'])
            ->where('user_id', $validated['employee_id'])
            ->first();

        $validated['designation'] = optional(optional($employee)->designation)->name ?? $validated['designation'] ?? null;
        $validated['department'] = optional(optional($employee)->department)->name ?? $validated['department'] ?? null;
        $validated['attachment'] = $this->normalizeAttachmentPath($validated['attachment'] ?? null);

        $workFromHomeRequest->update($validated);

        return redirect()->back()->with('success', __('WFH request updated successfully.'));
    }

    public function destroy(WorkFromHomeRequest $workFromHomeRequest)
    {
        if (! Auth::user()->can('delete-wfh-applications')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        if (! $this->canManageAny()) {
            return redirect()->back()->with('error', __('Only admin can delete WFH requests.'));
        }

        if (! $this->isScopedRequest($workFromHomeRequest)) {
            return redirect()->back()->with('error', __('WFH request not found.'));
        }

        if (! in_array($workFromHomeRequest->status, ['pending', 'approved', 'rejected'], true)) {
            return redirect()->back()->with('error', __('WFH request status is invalid for delete.'));
        }

        $workFromHomeRequest->delete();

        return redirect()->back()->with('success', __('WFH request deleted successfully.'));
    }

    public function updateStatus(Request $request, WorkFromHomeRequest $workFromHomeRequest)
    {
        if (! Auth::user()->can('approve-wfh-applications')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'manager_comments' => 'nullable|string',
        ]);

        if (! $this->isScopedRequest($workFromHomeRequest)) {
            return redirect()->back()->with('error', __('WFH request not found.'));
        }

        $workFromHomeRequest->update([
            'status' => $validated['status'],
            'manager_comments' => $validated['manager_comments'] ?? null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        if ($validated['status'] === 'approved') {
            $workFromHomeRequest->createAttendanceRecords();
        }

        if ($this->hrNotifier) {
            try {
            $companyId = (int) ($workFromHomeRequest->created_by ?: creatorId());
            $employee = User::find($workFromHomeRequest->employee_id);
            $recipients = collect($employee ? [$employee] : [])->filter(fn ($u) => ! empty($u->email));
            $this->hrNotifier->notifyEvent(
                'wfh_status',
                $companyId,
                [
                    '{app_name}' => config('app.name', 'HRMS'),
                    '{employee_name}' => $employee->name ?? '',
                    '{start_date}' => (string) optional($workFromHomeRequest->start_date)->format('Y-m-d'),
                    '{end_date}' => (string) optional($workFromHomeRequest->end_date)->format('Y-m-d'),
                    '{total_days}' => (string) ((optional($workFromHomeRequest->start_date)->diffInDays($workFromHomeRequest->end_date) ?? 0) + 1),
                    '{reason}' => (string) ($workFromHomeRequest->reason ?? ''),
                    '{status}' => ucfirst((string) $validated['status']),
                    '{manager_comments}' => (string) ($validated['manager_comments'] ?? '-'),
                    '{date}' => (string) optional($workFromHomeRequest->start_date)->format('Y-m-d'),
                    '{requested_clock_in}' => '-',
                    '{requested_clock_out}' => '-',
                ],
                $recipients,
                [
                    'entity' => 'wfh_request',
                    'entity_id' => $workFromHomeRequest->id,
                ]
            );
            } catch (\Throwable $e) {
                \Log::warning('WFH status notification failed: '.$e->getMessage());
            }
        }

        return redirect()->back()->with('success', __('WFH request status updated successfully.'));
    }
}
