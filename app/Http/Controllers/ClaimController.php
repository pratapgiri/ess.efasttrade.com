<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\ClaimWorkflow;
use App\Models\CompanyClaimConfig;
use App\Models\Department;
use App\Models\User;
use App\Services\ClaimWorkflowService;
use App\Services\HrEventNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ClaimController extends Controller
{
    public function __construct(
        private ClaimWorkflowService $workflow,
        private HrEventNotificationService $hrNotifier
    ) {
    }

    public function index(Request $request)
    {
        if (! $this->canAccessMyClaims()) {
            return redirect()->route('dashboard')->with('error', __('Permission Denied.'));
        }

        $companyId = $this->workflow->companyId();
        $employeeId = (int) Auth::id();

        $paginator = $this->workflow->listMyClaims($employeeId, [
            'claim_type' => $request->claim_type,
            'month_year' => $request->month_year ?? now()->format('Y-m'),
            'status' => $request->status ?? 'all',
            'search' => $request->search,
            'per_page' => $request->per_page ?? 10,
        ]);

        $claims = $paginator->through(fn (Claim $c) => $this->workflow->transformClaimForList($c));

        return Inertia::render('hr/claims/index', [
            'claims' => $claims,
            'claimConfig' => $this->workflow->getVisibilityConfig($companyId),
            'filters' => $request->only(['claim_type', 'month_year', 'status', 'search', 'per_page']),
            'activeClaimType' => $request->get('claim_type', 'expense'),
            'defaultMonthYear' => $request->get('month_year', now()->format('Y-m')),
        ]);
    }

    public function store(Request $request)
    {
        if (! Auth::user()->can('create-claims') && ! Auth::user()->can('manage-own-claims')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $this->validateClaimPayload($request);
        $companyId = $this->workflow->companyId();
        $employeeId = (int) Auth::id();

        if (! $this->workflow->isClaimTypeEnabled($validated['claim_type'], $companyId)) {
            return redirect()->back()->with('error', __('This claim type is not enabled for your company.'));
        }

        try {
            $this->workflow->assertClaimDateAllowed($validated['claim_date'], $validated['claim_type'], $companyId);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $isForward = $request->input('action') === 'forward';

        $claim = Claim::create([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'claim_type' => $validated['claim_type'],
            'claim_no' => $this->workflow->generateClaimNo($companyId),
            'claim_date' => $validated['claim_date'],
            'amount' => $this->workflow->calculateAmount($validated['claim_type'], $validated),
            'status' => $isForward ? 'pending' : 'draft',
            'route_name' => $this->workflow->getRouteName($validated['claim_type']),
            'pending_role_type' => $isForward ? 'Manager' : 'Employee',
            'current_workflow_level' => $isForward ? 2 : 1,
            'pending_user_id' => $isForward ? null : $employeeId,
            'pending_since' => $isForward ? now() : null,
            'submitted_at' => $isForward ? now() : null,
            'narration' => $validated['narration'] ?? null,
            'employee_remark' => $validated['employee_remark'] ?? null,
            'bill_no' => $validated['bill_no'] ?? null,
            'bill_date' => $validated['bill_date'] ?? null,
            'details' => $validated['details'] ?? [],
            'created_by' => creatorId(),
        ]);


        if ($request->hasFile('attachment')) {
            $this->workflow->storeAttachment($claim, $request->file('attachment'));
        }

        return redirect()->back()->with(
            'success',
            $isForward ? __('Claim submitted successfully.') : __('Claim saved as draft.')
        );
    }

    public function update(Request $request, Claim $claim)
    {
        if (! $this->canModifyClaim($claim)) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $this->validateClaimPayload($request, $claim->claim_type);

        try {
            $this->workflow->assertClaimDateAllowed($validated['claim_date'], $claim->claim_type, (int) $claim->company_id);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $claim->fill([
            'claim_date' => $validated['claim_date'],
            'amount' => $this->workflow->calculateAmount($claim->claim_type, $validated),
            'narration' => $validated['narration'] ?? $claim->narration,
            'employee_remark' => $validated['employee_remark'] ?? $claim->employee_remark,
            'manager_remark' => $validated['manager_remark'] ?? $claim->manager_remark,
            'final_remarks' => $validated['final_remark'] ?? $claim->final_remarks,
            'passed_amount' => $validated['passed_amount'] ?? $claim->passed_amount,
            'bill_no' => $validated['bill_no'] ?? $claim->bill_no,
            'bill_date' => $validated['bill_date'] ?? $claim->bill_date,
            'details' => $validated['details'] ?? $claim->details,
        ]);

        if ($request->hasFile('attachment')) {
            $this->workflow->storeAttachment($claim, $request->file('attachment'));
        }

        $claim->save();

        return $this->handleWorkflowAction($request, $claim);
    }

    public function show(Request $request, Claim $claim)
    {
        if (! $this->canViewClaim($claim)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => __('Permission Denied.')], 403);
            }

            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $claim->load([
            'employee:id,name',
            'pendingUser:id,name',
            'attachments' => fn ($q) => $q->select('id', 'claim_id', 'file_name', 'file_path')->orderByDesc('id')->limit(1),
            'workflowLogs.actor:id,name',
        ]);

        return response()->json([
            'claim' => $this->workflow->transformClaimDetail($claim),
        ]);
    }

    public function destroy(Claim $claim)
    {
        if (! $this->canDeleteClaim($claim)) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        DB::transaction(function () use ($claim) {
            $claim->load('attachments');
            foreach ($claim->attachments as $attachment) {
                if ($attachment->file_path) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
            }
            $claim->delete();
        });

        return redirect()->back()->with('success', __('Claim deleted successfully.'));
    }

    public function approvals(Request $request)
    {
        if (! Auth::user()->can('manage-claim-approvals')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $companyId = $this->workflow->companyId();
        $paginator = $this->workflow->listApprovals(Auth::id(), [
            'claim_type' => $request->claim_type,
            'month_year' => $request->month_year ?? now()->format('Y-m'),
            'employee_id' => $request->employee_id ?? 'all',
            'status' => $request->status ?? 'all',
            'search' => $request->search,
            'per_page' => $request->per_page ?? 10,
        ]);

        $claims = $paginator->through(fn (Claim $c) => $this->workflow->transformClaimForList($c));

        return Inertia::render('hr/claims/approvals', [
            'claims' => $claims,
            'employees' => $this->workflow->distinctPendingEmployees(Auth::id()),
            'claimConfig' => $this->workflow->getVisibilityConfig($companyId),
            'filters' => $request->only(['claim_type', 'month_year', 'employee_id', 'status', 'search', 'per_page']),
            'activeClaimType' => $request->get('claim_type', 'expense'),
        ]);
    }

    public function workflows(Request $request)
    {
        if (! Auth::user()->can('manage-claims')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $companyId = $this->workflow->companyId();
        $this->workflow->seedDefaultWorkflows($companyId);

        $workflows = ClaimWorkflow::query()
            ->where('company_id', $companyId)
            ->orderBy('claim_type')
            ->orderBy('route_name')
            ->orderBy('serial_number')
            ->get()
            ->groupBy(fn ($w) => $w->claim_type.'|'.$w->route_name)
            ->map(function ($group, $key) {
                [$claimType, $routeName] = explode('|', $key, 2);
                $first = $group->first();

                return [
                    'id' => $first->id,
                    'workflow_name' => str_replace('_', ' ', $routeName),
                    'claim_type' => $claimType,
                    'company_name' => Auth::user()->name,
                    'total_levels' => $group->count(),
                    'status' => 'active',
                    'route_name' => $routeName,
                    'levels' => $group->map(fn ($l) => [
                        'id' => (string) $l->id,
                        'serial_number' => $l->serial_number,
                        'emp_type' => $l->role_type,
                        'approver_user_id' => $l->approver_user_id ? (string) $l->approver_user_id : '',
                        'department_id' => $l->department_id ? (string) $l->department_id : '',
                    ])->values()->all(),
                ];
            })
            ->values();

        $approvers = User::query()
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name']);

        $departments = Department::query()
            ->whereIn('created_by', getCompanyAndUsersId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('hr/claims/workflow', [
            'workflows' => $workflows,
            'approvers' => $approvers,
            'departments' => $departments,
            'routeName' => $request->get('route_name', 'EXPENSE_ROUTE'),
        ]);
    }

    public function saveWorkflow(Request $request)
    {
        if (! Auth::user()->can('manage-claims')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $request->validate([
            'claim_type' => 'required|in:expense,local_conveyance,intercity_travel',
            'workflow_name' => 'nullable|string|max:255',
            'levels' => 'required|array|min:1',
            'levels.*.serial_number' => 'required|integer|min:1',
            'levels.*.emp_type' => 'required|string|max:32',
            'levels.*.approver_user_id' => 'nullable',
            'levels.*.department_id' => 'nullable',
        ]);

        $this->workflow->syncWorkflowDefinition(
            $this->workflow->companyId(),
            $validated['claim_type'],
            $validated['workflow_name'] ?? $this->workflow->getRouteName($validated['claim_type']),
            $validated['levels']
        );

        return redirect()->back()->with('success', __('Workflow saved successfully.'));
    }

    public function config()
    {
        if (! Auth::user()->can('manage-claims')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $companyId = $this->workflow->companyId();
        $config = CompanyClaimConfig::forCompany($companyId);

        return Inertia::render('hr/claims/config', [
            'config' => [
                'local_conveyance_available' => $config->conveyance_enabled,
                'intercity_conveyance_available' => $config->travel_enabled,
                'expenses_available' => $config->expense_enabled,
            ],
            'claimConfig' => $config->toVisibilityArray(),
        ]);
    }

    public function saveConfig(Request $request)
    {
        if (! Auth::user()->can('manage-claims')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $request->validate([
            'expenses_available' => 'required|boolean',
            'local_conveyance_available' => 'required|boolean',
            'intercity_conveyance_available' => 'required|boolean',
        ]);

        $config = CompanyClaimConfig::forCompany($this->workflow->companyId());
        $config->update([
            'expense_enabled' => $validated['expenses_available'],
            'conveyance_enabled' => $validated['local_conveyance_available'],
            'travel_enabled' => $validated['intercity_conveyance_available'],
        ]);

        return redirect()->back()->with('success', __('Claim configuration saved.'));
    }

    public function viewAttachment(Claim $claim, int $attachment)
    {
        if (! $this->canViewClaim($claim)) {
            abort(403, __('Permission Denied.'));
        }

        $record = $claim->attachments()->where('id', $attachment)->firstOrFail();
        $fullPath = $this->workflow->resolveAttachmentFullPath($record->file_path);

        if (! is_file($fullPath)) {
            abort(404, __('Attachment file not found.'));
        }

        return response()->file($fullPath);
    }

    public function downloadAttachment(Claim $claim, int $attachment)
    {
        if (! $this->canViewClaim($claim)) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $record = $claim->attachments()->where('id', $attachment)->firstOrFail();
        $fullPath = $this->workflow->resolveAttachmentFullPath($record->file_path);

        if (! is_file($fullPath)) {
            return redirect()->back()->with('error', __('Attachment file not found.'));
        }

        return response()->download($fullPath, $record->file_name);
    }

    private function handleWorkflowAction(Request $request, Claim $claim)
    {
        try {
            $action = $request->input('action', 'save');

            switch ($action) {
                case 'forward':
                    $claim = $this->workflow->forward($claim, $request->input('employee_remark'));
                    $this->notifyClaim('claim_forward', $claim);
                    break;
                case 'reject':
                    $claim = $this->workflow->reject(
                        $claim,
                        (int) $request->input('reject_to_step'),
                        $request->input('manager_remark')
                    );
                    $this->notifyClaim('claim_reject', $claim);
                    break;
                case 'approve':
                    $claim = $this->workflow->approve(
                        $claim,
                        $request->filled('passed_amount') ? (float) $request->passed_amount : null,
                        $request->input('final_remark'),
                        $request->input('manager_remark')
                    );
                    $this->notifyClaim('claim_approve', $claim);
                    break;
                case 'cancel':
                    $claim = $this->workflow->cancel($claim, $request->input('employee_remark'));
                    break;
                default:
                    break;
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', __('Claim updated successfully.'));
    }

    private function validateClaimPayload(Request $request, ?string $claimType = null): array
    {
        $claimType = $claimType ?? $request->input('claim_type', 'expense');

        $rules = [
            'claim_type' => 'sometimes|in:expense,local_conveyance,intercity_travel',
            'claim_date' => 'required|date',
            'amount' => 'nullable|numeric|min:0',
            'narration' => 'nullable|string',
            'employee_remark' => 'nullable|string',
            'manager_remark' => 'nullable|string',
            'final_remark' => 'nullable|string',
            'passed_amount' => 'nullable|numeric|min:0',
            'bill_no' => 'nullable|string|max:128',
            'bill_date' => 'nullable|date',
            'details' => 'nullable|array',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'action' => 'nullable|in:draft,forward,reject,approve,cancel,save',
            'employee_id' => 'nullable|exists:users,id',
        ];

        return Validator::make($request->all(), $rules)->validate();
    }

    private function canAccessMyClaims(): bool
    {
        $user = Auth::user();

        if ($user->type === 'employee') {
            return $user->can('manage-own-claims')
                || $user->can('view-claims')
                || $user->can('create-claims');
        }

        return $user->can('manage-own-claims')
            || $user->can('view-claims')
            || $user->can('create-claims');
    }

    private function canModifyClaim(Claim $claim): bool
    {
        if ((int) $claim->company_id !== (int) $this->workflow->companyId()) {
            return false;
        }

        if (Auth::user()->can('manage-claims')) {
            return $this->workflow->canEmployeeEdit($claim);
        }

        if ((int) $claim->employee_id !== (int) Auth::id()) {
            return false;
        }

        if (! Auth::user()->can('edit-claims') && ! Auth::user()->can('manage-own-claims')) {
            return false;
        }

        return $this->workflow->canEmployeeEdit($claim);
    }

    private function canViewClaim(Claim $claim): bool
    {
        if ((int) $claim->company_id !== $this->workflow->companyId()) {
            return false;
        }

        $user = Auth::user();

        // Admins can view any claim in their company
        if ($user->can('manage-claims')) {
            return true;
        }

        // Approvers can view claims currently pending their action
        if ($user->can('manage-claim-approvals') && (int) $claim->pending_user_id === (int) $user->id) {
            return true;
        }

        // Employees can view their own claims
        if ((int) $claim->employee_id !== (int) $user->id) {
            return false;
        }

        return $user->can('manage-own-claims')
            || $user->can('view-claims')
            || $user->can('create-claims');
    }

    private function canDeleteClaim(Claim $claim): bool
    {
        if ((int) $claim->company_id !== (int) $this->workflow->companyId()) {
            return false;
        }

        $user = Auth::user();
        $canDelete = $user->can('delete-claims')
            || $user->can('manage-claims')
            || $user->can('manage-own-claims');

        if (! $canDelete) {
            return false;
        }

        return $this->workflow->canEmployeeDelete($claim);
    }

    private function notifyClaim(string $eventKey, Claim $claim): void
    {
        try {
            $companyId = (int) $claim->company_id;
            $variables = [
                '{employee_name}' => $claim->employee?->name ?? '',
                '{claim_no}' => $claim->claim_no,
                '{claim_type}' => $claim->claim_type,
                '{amount}' => (string) $claim->amount,
                '{status}' => $claim->status,
                '{pending_at}' => $claim->pending_role_type ?? '-',
            ];

            $recipients = collect();
            if ($claim->pending_user_id) {
                $pending = User::find($claim->pending_user_id);
                if ($pending) {
                    $recipients->push($pending);
                }
            }

            if ($recipients->isEmpty()) {
                $recipients = $this->hrNotifier->getApproversByPermission($companyId, 'manage-claim-approvals');
            }

            $this->hrNotifier->notifyEvent($eventKey, $companyId, $variables, $recipients, [
                'claim_id' => $claim->id,
            ]);
        } catch (\Throwable $e) {
            // Non-blocking notifications
        }
    }
}
