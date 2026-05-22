<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClaimAttachment;
use App\Models\ClaimWorkflow;
use App\Models\ClaimWorkflowLog;
use App\Models\CompanyClaimConfig;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ClaimWorkflowService
{
    private ?int $cachedCompanyId = null;

    public const ROUTE_MAP = [
        'expense' => 'EXPENSE_ROUTE',
        'local_conveyance' => 'CONVEYANCE_ROUTE',
        'intercity_travel' => 'TRAVEL_ROUTE',
    ];

    public function companyId(): int
    {
        if ($this->cachedCompanyId !== null) {
            return $this->cachedCompanyId;
        }

        $userId = Auth::id();
        $companyId = $userId ? getCompanyId($userId) : null;

        $this->cachedCompanyId = (int) ($companyId ?? $userId ?? 0);

        return $this->cachedCompanyId;
    }

    public function getVisibilityConfig(?int $companyId = null): array
    {
        $companyId = $companyId ?? $this->companyId();

        return cache()->remember("claim_visibility_{$companyId}", 300, function () use ($companyId) {
            return CompanyClaimConfig::forCompany($companyId)->toVisibilityArray();
        });
    }

    public function getRouteName(string $claimType): string
    {
        return self::ROUTE_MAP[$claimType] ?? 'EXPENSE_ROUTE';
    }

    public function getWorkflowLevels(int $companyId, string $claimType, ?string $routeName = null): Collection
    {
        if (! Schema::hasTable('claim_workflows')) {
            return collect();
        }

        $routeName = $routeName ?? $this->getRouteName($claimType);

        return ClaimWorkflow::query()
            ->where('company_id', $companyId)
            ->where('claim_type', $claimType)
            ->where('route_name', $routeName)
            ->where('status', 'active')
            ->orderBy('serial_number')
            ->get();
    }

    public function seedDefaultWorkflows(int $companyId): void
    {
        if (! Schema::hasTable('claim_workflows')) {
            return;
        }

        $defaults = [
            'expense' => [
                ['serial_number' => 1, 'role_type' => 'Employee'],
                ['serial_number' => 2, 'role_type' => 'Manager'],
                ['serial_number' => 3, 'role_type' => 'HR'],
                ['serial_number' => 4, 'role_type' => 'Accounts'],
            ],
            'local_conveyance' => [
                ['serial_number' => 1, 'role_type' => 'Employee'],
                ['serial_number' => 2, 'role_type' => 'Manager'],
                ['serial_number' => 3, 'role_type' => 'Accounts'],
            ],
            'intercity_travel' => [
                ['serial_number' => 1, 'role_type' => 'Employee'],
                ['serial_number' => 2, 'role_type' => 'Manager'],
                ['serial_number' => 3, 'role_type' => 'HR'],
                ['serial_number' => 4, 'role_type' => 'Accounts'],
            ],
        ];

        foreach ($defaults as $claimType => $levels) {
            $routeName = $this->getRouteName($claimType);
            if ($this->getWorkflowLevels($companyId, $claimType, $routeName)->isNotEmpty()) {
                continue;
            }

            foreach ($levels as $level) {
                ClaimWorkflow::create([
                    'company_id' => $companyId,
                    'route_name' => $routeName,
                    'claim_type' => $claimType,
                    'serial_number' => $level['serial_number'],
                    'role_type' => $level['role_type'],
                    'status' => 'active',
                    'created_by' => $companyId,
                ]);
            }
        }
    }

    public function syncWorkflowDefinition(int $companyId, string $claimType, string $workflowName, array $levels): void
    {
        $routeName = $this->getRouteName($claimType);

        DB::transaction(function () use ($companyId, $claimType, $routeName, $levels) {
            ClaimWorkflow::query()
                ->where('company_id', $companyId)
                ->where('claim_type', $claimType)
                ->where('route_name', $routeName)
                ->delete();

            foreach ($levels as $index => $level) {
                ClaimWorkflow::create([
                    'company_id' => $companyId,
                    'route_name' => $routeName,
                    'claim_type' => $claimType,
                    'serial_number' => (int) ($level['serial_number'] ?? ($index + 1)),
                    'role_type' => $level['role_type'] ?? $level['emp_type'] ?? 'Manager',
                    'approver_user_id' => ! empty($level['approver_user_id']) ? (int) $level['approver_user_id'] : null,
                    'department_id' => ! empty($level['department_id']) ? (int) $level['department_id'] : null,
                    'status' => 'active',
                    'created_by' => creatorId(),
                ]);
            }
        });
    }

    public function resolvePendingUserId(ClaimWorkflow $level, Claim $claim): ?int
    {
        if ($level->approver_user_id) {
            return (int) $level->approver_user_id;
        }

        if ($level->role_type === 'Employee') {
            return (int) $claim->employee_id;
        }

        $companyIds = getCompanyAndUsersId();

        $query = User::query()
            ->where('status', 'active')
            ->whereIn('created_by', $companyIds)
            ->where('id', '!=', $claim->employee_id);

        if ($level->role_type === 'Manager') {
            $query->whereHas('roles', fn ($q) => $q->where('name', 'manager'));
        } elseif ($level->role_type === 'HR') {
            $query->where(function ($q) {
                $q->permission('manage-claim-approvals')
                    ->orWhereHas('roles', fn ($r) => $r->where('name', 'hr'));
            });
        } elseif ($level->role_type === 'Accounts') {
            $query->permission('manage-claim-approvals');
        } else {
            $query->permission('manage-claim-approvals');
        }

        if ($level->department_id) {
            $query->whereHas('employee', fn ($e) => $e->where('department_id', $level->department_id));
        }

        return $query->value('id');
    }

    public function getLevelBySerial(Collection $levels, int $serial): ?ClaimWorkflow
    {
        return $levels->firstWhere('serial_number', $serial);
    }

    public function generateClaimNo(int $companyId): string
    {
        $year = now()->format('Y');
        $count = Claim::query()
            ->where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('CLM-%s-%04d', $year, $count);
    }

    public function calculateAmount(string $claimType, array $data): float
    {
        if ($claimType === 'intercity_travel') {
            $details = $data['details'] ?? [];
            $ticket = (float) ($details['ticket_amount'] ?? 0);
            $hotel = (float) ($details['hotel_amount'] ?? 0);
            $food = (float) ($details['food_amount'] ?? 0);

            return round($ticket + $hotel + $food, 2);
        }

        return round((float) ($data['amount'] ?? 0), 2);
    }

    public function applyPendingAtLevel(Claim $claim, ClaimWorkflow $level): void
    {
        $claim->current_workflow_level = $level->serial_number;
        $claim->pending_role_type = $level->role_type;
        $claim->pending_user_id = $this->resolvePendingUserId($level, $claim);
        $claim->pending_since = now();
    }

    public function logAction(
        Claim $claim,
        string $action,
        ?string $remarks = null,
        ?ClaimWorkflow $from = null,
        ?ClaimWorkflow $to = null,
        ?int $fromPendingUserId = null,
        ?string $fromPendingRole = null
    ): void {
        ClaimWorkflowLog::create([
            'claim_id' => $claim->id,
            'workflow_level' => $claim->current_workflow_level,
            'action' => $action,
            'remarks' => $remarks,
            'action_by' => Auth::id(),
            'from_pending_role' => $fromPendingRole ?? $from?->role_type,
            'to_pending_role' => $to?->role_type,
            'from_pending_user_id' => $fromPendingUserId,
            'to_pending_user_id' => $claim->pending_user_id,
            'action_date' => now(),
            'created_by' => creatorId(),
        ]);
    }

    public function canUserAct(Claim $claim, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->type === 'superadmin' || $user->type === 'company') {
            return true;
        }

        return (int) $claim->pending_user_id === (int) $user->id;
    }

    public function isEmployeeStage(Claim $claim): bool
    {
        if (strcasecmp((string) ($claim->pending_role_type ?? ''), 'Employee') === 0) {
            return true;
        }

        if ((int) $claim->pending_user_id === (int) $claim->employee_id) {
            return true;
        }

        return $claim->status === 'draft' && (int) $claim->current_workflow_level <= 1;
    }

    public function isFinalLevel(Claim $claim, Collection $levels): bool
    {
        $max = (int) $levels->max('serial_number');

        return (int) $claim->current_workflow_level >= $max;
    }

    public function forward(Claim $claim, ?string $remarks = null): Claim
    {
        $levels = $this->getWorkflowLevels($claim->company_id, $claim->claim_type, $claim->route_name);
        if ($levels->isEmpty()) {
            throw new \RuntimeException(__('Workflow is not configured for this claim type.'));
        }

        $current = $this->getLevelBySerial($levels, (int) $claim->current_workflow_level);
        $from = $current;

        if ($claim->status === 'draft') {
            if ((int) $claim->employee_id !== (int) Auth::id() && ! Auth::user()?->can('manage-claims')) {
                throw new \RuntimeException(__('Only the employee can submit this claim.'));
            }

            $fromUserId = $claim->pending_user_id;
            $fromRole = $claim->pending_role_type;
            $claim->status = 'pending';
            $claim->submitted_at = now();
            $next = $this->getLevelBySerial($levels, 2) ?? $levels->where('serial_number', '>', 1)->first();
            if (! $next) {
                throw new \RuntimeException(__('Workflow must have at least two levels.'));
            }
            $this->applyPendingAtLevel($claim, $next);
            $this->logAction($claim, 'submit', $remarks, $from, $next, $fromUserId, $fromRole);
            $claim->save();

            return $claim->fresh(['employee', 'pendingUser', 'attachments', 'workflowLogs.actor']);
        }

        if (! $this->canUserAct($claim)) {
            throw new \RuntimeException(__('You are not authorized to forward this claim.'));
        }

        $fromUserId = $claim->pending_user_id;
        $fromRole = $claim->pending_role_type;
        $nextSerial = (int) $claim->current_workflow_level + 1;
        $next = $this->getLevelBySerial($levels, $nextSerial);

        if (! $next) {
            throw new \RuntimeException(__('Claim is already at the final workflow stage.'));
        }

        $this->applyPendingAtLevel($claim, $next);
        $claim->status = 'pending';
        $this->logAction($claim, 'forward', $remarks, $from, $next, $fromUserId, $fromRole);
        $claim->save();

        return $claim->fresh(['employee', 'pendingUser', 'attachments', 'workflowLogs.actor']);
    }

    public function reject(Claim $claim, int $rejectToSerial, ?string $remarks = null): Claim
    {
        if (! $this->canUserAct($claim)) {
            throw new \RuntimeException(__('You are not authorized to reject this claim.'));
        }

        if ($this->isEmployeeStage($claim)) {
            throw new \RuntimeException(__('Employees cannot reject claims at this stage.'));
        }

        $levels = $this->getWorkflowLevels($claim->company_id, $claim->claim_type, $claim->route_name);
        $target = $this->getLevelBySerial($levels, $rejectToSerial);

        if (! $target || $rejectToSerial >= (int) $claim->current_workflow_level) {
            throw new \RuntimeException(__('Invalid reject target level.'));
        }

        $from = $this->getLevelBySerial($levels, (int) $claim->current_workflow_level);
        $fromUserId = $claim->pending_user_id;
        $fromRole = $claim->pending_role_type;
        $this->applyPendingAtLevel($claim, $target);
        $claim->status = 'pending';
        $this->logAction($claim, 'reject', $remarks, $from, $target, $fromUserId, $fromRole);
        $claim->save();

        return $claim->fresh(['employee', 'pendingUser', 'attachments', 'workflowLogs.actor']);
    }

    public function approve(Claim $claim, ?float $passedAmount = null, ?string $finalRemarks = null, ?string $managerRemark = null): Claim
    {
        if (! $this->canUserAct($claim)) {
            throw new \RuntimeException(__('You are not authorized to approve this claim.'));
        }

        $levels = $this->getWorkflowLevels($claim->company_id, $claim->claim_type, $claim->route_name);

        if (! $this->isFinalLevel($claim, $levels)) {
            throw new \RuntimeException(__('Approve is only allowed at the final workflow level.'));
        }

        if ($managerRemark !== null) {
            $claim->manager_remark = $managerRemark;
        }
        if ($finalRemarks !== null) {
            $claim->final_remarks = $finalRemarks;
        }

        $claim->passed_amount = $passedAmount ?? $claim->amount;
        $claim->status = 'approved';
        $claim->pending_user_id = null;
        $claim->pending_role_type = null;
        $claim->pending_since = null;

        $from = $this->getLevelBySerial($levels, (int) $claim->current_workflow_level);
        $fromUserId = $claim->pending_user_id;
        $fromRole = $claim->pending_role_type;
        $this->logAction($claim, 'approve', $finalRemarks, $from, null, $fromUserId, $fromRole);
        $claim->save();

        return $claim->fresh(['employee', 'pendingUser', 'attachments', 'workflowLogs.actor']);
    }

    public function cancel(Claim $claim, ?string $remarks = null): Claim
    {
        if (! $this->canEmployeeCancel($claim)) {
            throw new \RuntimeException(__('You are not allowed to cancel this claim.'));
        }

        $claim->status = 'cancelled';
        $claim->pending_user_id = null;
        $claim->pending_role_type = null;
        $claim->pending_since = null;
        $this->logAction($claim, 'cancel', $remarks);
        $claim->save();

        return $claim->fresh(['employee', 'pendingUser', 'attachments', 'workflowLogs.actor']);
    }

    public function storeAttachment(Claim $claim, UploadedFile $file): ClaimAttachment
    {
        $directory = 'media/claims/'.$claim->company_id;
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName()) ?: 'attachment';
        $fileName = uniqid('', true).'_'.$safeName;

        $path = $file->storeAs($directory, $fileName, 'public');

        return ClaimAttachment::create([
            'claim_id' => $claim->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'created_by' => Auth::id(),
        ]);
    }

    public function resolveAttachmentFullPath(string $filePath): string
    {
        $path = ltrim($filePath, '/');

        if (str_starts_with($path, 'media/')) {
            return storage_path('app/public/'.$path);
        }

        if (str_starts_with($path, 'claims/')) {
            return storage_path('app/public/'.$path);
        }

        return storage_path('app/public/media/'.$path);
    }

    public function listMyClaims(int $employeeId, array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $companyId = $this->companyId();
        $monthYear = $filters['month_year'] ?? now()->format('Y-m');

        $query = Claim::query()
            ->select([
                'id', 'company_id', 'employee_id', 'claim_no', 'claim_type', 'claim_date',
                'amount', 'status', 'pending_role_type', 'pending_user_id', 'pending_since', 'narration',
            ])
            ->with([
                'employee:id,name',
                'pendingUser:id,name',
                'attachments' => fn ($q) => $q->select('id', 'claim_id', 'file_name')->orderByDesc('id')->limit(1),
            ])
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId);

        if (! empty($filters['claim_type'])) {
            $query->where('claim_type', $filters['claim_type']);
        }

        if ($monthYear) {
            $start = Carbon::createFromFormat('Y-m', $monthYear)->startOfMonth();
            $end = Carbon::createFromFormat('Y-m', $monthYear)->endOfMonth();
            $query->whereBetween('claim_date', [$start, $end]);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('claim_no', 'like', "%{$search}%")
                    ->orWhere('narration', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('claim_date')->paginate($filters['per_page'] ?? 10);
    }

    public function listApprovals(int $approverId, array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $companyId = $this->companyId();
        $monthYear = $filters['month_year'] ?? now()->format('Y-m');

        $query = Claim::with(['employee', 'pendingUser'])
            ->where('company_id', $companyId)
            ->where('pending_user_id', $approverId)
            ->whereIn('status', ['pending']);

        if (! empty($filters['claim_type'])) {
            $query->where('claim_type', $filters['claim_type']);
        }

        if ($monthYear) {
            $start = Carbon::createFromFormat('Y-m', $monthYear)->startOfMonth();
            $end = Carbon::createFromFormat('Y-m', $monthYear)->endOfMonth();
            $query->whereBetween('claim_date', [$start, $end]);
        }

        if (! empty($filters['employee_id']) && $filters['employee_id'] !== 'all') {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('claim_no', 'like', "%{$search}%")
                    ->orWhere('narration', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn ($e) => $e->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->orderByDesc('pending_since')->paginate($filters['per_page'] ?? 10);
    }

    public function distinctPendingEmployees(int $approverId): Collection
    {
        return Claim::query()
            ->where('company_id', $this->companyId())
            ->where('pending_user_id', $approverId)
            ->where('status', 'pending')
            ->with('employee:id,name')
            ->get()
            ->pluck('employee')
            ->filter()
            ->unique('id')
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values();
    }

    public function transformClaimForList(Claim $claim): array
    {
        $attachment = $claim->attachments->first();

        return [
            'id' => $claim->id,
            'claim_no' => $claim->claim_no,
            'date' => $claim->claim_date?->format('Y-m-d'),
            'claim_type' => $claim->claim_type,
            'amount' => (float) $claim->amount,
            'status' => $claim->status,
            'pending_at' => $claim->pending_role_type ?? ($claim->pendingUser?->name ?? '-'),
            'pending_since' => $claim->pending_since?->format('Y-m-d'),
            'narration' => $claim->narration ?? '',
            'employee_name' => $claim->employee?->name,
            'employee_id' => $claim->employee_id,
            'has_attachment' => $attachment !== null,
            'attachment_name' => $attachment?->file_name,
        ];
    }

    /**
     * Lightweight payload for My Claims view modal (no workflow engine queries).
     */
    public function transformClaimForView(Claim $claim): array
    {
        $attachment = $claim->attachments->first();
        $details = is_array($claim->details) ? $claim->details : [];

        return [
            'id' => $claim->id,
            'claim_no' => $claim->claim_no,
            'claim_type' => $claim->claim_type,
            'claim_date' => $claim->claim_date?->format('Y-m-d'),
            'amount' => $this->formatAmountForApi($claim->amount, $details),
            'narration' => (string) ($claim->narration ?? $details['expense_details'] ?? $details['purpose'] ?? ''),
            'bill_no' => (string) ($claim->bill_no ?? $details['bill_no'] ?? ''),
            'bill_date' => $claim->bill_date?->format('Y-m-d') ?: ($details['bill_date'] ?? null),
            'employee_remark' => (string) ($claim->employee_remark ?? ''),
            'manager_remark' => $claim->manager_remark,
            'final_remark' => $claim->final_remarks,
            'passed_amount' => $claim->passed_amount ? (string) $claim->passed_amount : '',
            'status' => $claim->status,
            'pending_at' => $claim->pending_role_type ?? '-',
            'employee_name' => $claim->employee?->name ?? '',
            'details' => $details,
            'attachment' => $attachment ? $this->publicAttachmentUrl($attachment->file_path) : null,
            'attachment_download' => $attachment
                ? route('hr.claims.attachments.download', ['claim' => $claim->id, 'attachment' => $attachment->id])
                : null,
            'attachment_name' => $attachment?->file_name,
            'attachment_is_image' => $attachment ? $this->attachmentIsImage($attachment->file_name) : false,
        ];
    }

    public function transformClaimDetail(Claim $claim, ?User $viewer = null): array
    {
        $viewer = $viewer ?? Auth::user();
        $levels = $this->getWorkflowLevels($claim->company_id, $claim->claim_type, $claim->route_name);
        $isPendingUser = $this->canUserAct($claim, $viewer);
        $isEmployee = (int) $claim->employee_id === (int) $viewer?->id;
        $isFinal = $this->isFinalLevel($claim, $levels);
        $attachment = $claim->attachments->last();
        $details = is_array($claim->details) ? $claim->details : [];

        return [
            'id' => $claim->id,
            'claim_no' => $claim->claim_no,
            'claim_type' => $claim->claim_type,
            'claim_date' => $claim->claim_date?->format('Y-m-d'),
            'amount' => $this->formatAmountForApi($claim->amount, $details),
            'narration' => (string) ($claim->narration ?? $details['expense_details'] ?? $details['purpose'] ?? ''),
            'bill_no' => (string) ($claim->bill_no ?? $details['bill_no'] ?? ''),
            'bill_date' => $claim->bill_date?->format('Y-m-d') ?: ($details['bill_date'] ?? null),
            'employee_remark' => (string) ($claim->employee_remark ?? ''),
            'manager_remark' => $claim->manager_remark,
            'final_remark' => $claim->final_remarks,
            'passed_amount' => $claim->passed_amount ? (string) $claim->passed_amount : '',
            'status' => $claim->status,
            'pending_at' => $claim->pending_role_type ?? '-',
            'employee_name' => $claim->employee?->name ?? '',
            'details' => $details,
            'attachment' => $attachment ? $this->publicAttachmentUrl($attachment->file_path) : null,
            'attachment_download' => $attachment
                ? route('hr.claims.attachments.download', ['claim' => $claim->id, 'attachment' => $attachment->id])
                : null,
            'attachment_name' => $attachment?->file_name,
            'attachment_is_image' => $attachment ? $this->attachmentIsImage($attachment->file_name) : false,
            'can_edit' => (bool) ($this->canEmployeeEdit($claim) && $isEmployee),
            'is_editable_employee' => $this->canEmployeeEdit($claim) && $isEmployee,
            'can_edit_manager_remark' => $isPendingUser && ! $isEmployee && ! $isFinal,
            'can_edit_final_remark' => $isPendingUser && $isFinal,
            'can_edit_passed_amount' => $isPendingUser && $isFinal,
            'final_remark_readonly' => ! ($isPendingUser && $isFinal),
            'can_forward' => $isPendingUser && ! $isFinal,
            'can_reject' => $isPendingUser && ! $this->isEmployeeStage($claim),
            'can_approve' => $isPendingUser && $isFinal,
            'can_cancel' => $this->canEmployeeCancel($claim) && $isEmployee,
            'workflow_logs' => $claim->workflowLogs->map(function (ClaimWorkflowLog $log) {
                return [
                    'action' => $log->action,
                    'from_pending_at' => $log->from_pending_role,
                    'to_pending_at' => $log->to_pending_role,
                    'performer' => $log->actor?->name,
                    'remarks' => $log->remarks,
                    'created_at' => $log->action_date?->format('Y-m-d H:i'),
                ];
            })->values()->all(),
            'previous_steps' => $levels
                ->where('serial_number', '<', (int) $claim->current_workflow_level)
                ->map(fn ($l) => ['serial_number' => $l->serial_number, 'emp_type' => $l->role_type])
                ->values()
                ->all(),
        ];
    }

    private function formatAmountForApi($amount, array $details): string
    {
        $value = (float) $amount;
        if ($value > 0) {
            return (string) $amount;
        }

        if (isset($details['amount']) && (float) $details['amount'] > 0) {
            return (string) $details['amount'];
        }

        return $value === 0.0 ? '0' : (string) $amount;
    }

    public function publicAttachmentUrl(?string $filePath): ?string
    {
        if (empty($filePath)) {
            return null;
        }

        $path = ltrim($filePath, '/');

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (! str_starts_with($path, 'media/') && ! str_starts_with($path, 'claims/')) {
            $path = 'media/'.$path;
        }

        return asset('storage/'.$path);
    }

    public function attachmentIsImage(?string $fileName): bool
    {
        if (empty($fileName)) {
            return false;
        }

        return (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $fileName);
    }

    public function isClaimTypeEnabled(string $claimType, ?int $companyId = null): bool
    {
        $config = CompanyClaimConfig::forCompany($companyId ?? $this->companyId());

        return match ($claimType) {
            'expense' => (bool) $config->expense_enabled,
            'local_conveyance' => (bool) $config->conveyance_enabled,
            'intercity_travel' => (bool) $config->travel_enabled,
            default => false,
        };
    }

    /**
     * When ALLOW_EXPENSES_CURRENT_MONTH_ONLY is enabled, expense claim_date must be in the current calendar month.
     */
    public function assertClaimDateAllowed(string $claimDate, string $claimType, ?int $companyId = null): void
    {
        if ($claimType !== 'expense') {
            return;
        }

        $config = CompanyClaimConfig::forCompany($companyId ?? $this->companyId());
        if (! $config->expense_current_month_only) {
            return;
        }

        $date = Carbon::parse($claimDate);
        if ($date->format('Y-m') !== Carbon::now()->format('Y-m')) {
            throw new \RuntimeException(__('Expense not allowed for selected month'));
        }
    }

    public function canEmployeeEdit(Claim $claim, ?User $viewer = null): bool
    {
        $viewer = $viewer ?? Auth::user();

        if (! in_array($claim->status, ['draft', 'pending'], true)) {
            return false;
        }

        if ((int) $claim->employee_id !== (int) $viewer?->id) {
            return false;
        }

        if ($claim->status === 'draft') {
            return true;
        }

        return $this->isEmployeeStage($claim);
    }

    public function canEmployeeCancel(Claim $claim, ?User $viewer = null): bool
    {
        $viewer = $viewer ?? Auth::user();

        if (! in_array($claim->status, ['draft', 'pending'], true)) {
            return false;
        }

        if ((int) $claim->employee_id !== (int) $viewer?->id) {
            return false;
        }

        if ($claim->status === 'draft') {
            return true;
        }

        return $this->isEmployeeStage($claim);
    }

    public function canEmployeeDelete(Claim $claim, ?User $viewer = null): bool
    {
        $viewer = $viewer ?? Auth::user();

        if ($claim->status !== 'draft') {
            return false;
        }

        if ($viewer?->can('manage-claims') && (int) $claim->company_id === $this->companyId()) {
            return true;
        }

        return (int) $claim->employee_id === (int) $viewer?->id;
    }
}
