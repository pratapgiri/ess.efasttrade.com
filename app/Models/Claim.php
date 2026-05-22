<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Claim extends BaseModel
{
    public function resolveRouteBinding($value, $field = null)
    {
        $userId = Auth::id();
        $companyId = $userId ? getCompanyId($userId) : null;

        return $this->where($field ?? 'id', $value)
            ->where('company_id', $companyId ?? $userId)
            ->firstOrFail();
    }

    protected $fillable = [
        'company_id',
        'employee_id',
        'claim_type',
        'claim_no',
        'claim_date',
        'amount',
        'status',
        'pending_user_id',
        'current_workflow_level',
        'route_name',
        'pending_role_type',
        'narration',
        'employee_remark',
        'manager_remark',
        'final_remarks',
        'passed_amount',
        'bill_no',
        'bill_date',
        'details',
        'pending_since',
        'submitted_at',
        'created_by',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'bill_date' => 'date',
        'amount' => 'decimal:2',
        'passed_amount' => 'decimal:2',
        'details' => 'array',
        'pending_since' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function pendingUser()
    {
        return $this->belongsTo(User::class, 'pending_user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClaimAttachment::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(ClaimWorkflowLog::class)->orderBy('action_date');
    }
}
