<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MonthlyPlAccrualLog extends BaseModel
{
    use HasFactory;

    protected $table = 'monthly_pl_accrual_logs';

    protected $fillable = [
        'batch_id',
        'company_id',
        'employee_id',
        'leave_type_id',
        'year',
        'month',
        'working_days',
        'entitled_days',
        'previous_credited_days',
        'delta_applied',
        'allocated_after',
        'status',
        'message',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'working_days' => 'decimal:2',
        'entitled_days' => 'integer',
        'previous_credited_days' => 'decimal:2',
        'delta_applied' => 'decimal:2',
        'allocated_after' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
