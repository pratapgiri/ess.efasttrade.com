<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalanceMonthlyAccrual extends BaseModel
{
    use HasFactory;

    protected $table = 'leave_balance_monthly_accruals';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'month',
        'working_days',
        'days_accrued',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'working_days' => 'decimal:2',
        'days_accrued' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
