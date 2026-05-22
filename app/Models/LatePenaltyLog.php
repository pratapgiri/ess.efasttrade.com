<?php

namespace App\Models;

class LatePenaltyLog extends BaseModel
{
    protected $fillable = [
        'employee_id',
        'company_id',
        'leave_type_id',
        'leave_application_id',
        'year',
        'month',
        'late_count',
        'expected_penalty_days',
        'already_applied_days',
        'applied_now_days',
        'status',
        'message',
        'meta',
        'processed_at',
    ];

    protected $casts = [
        'expected_penalty_days' => 'decimal:2',
        'already_applied_days' => 'decimal:2',
        'applied_now_days' => 'decimal:2',
        'meta' => 'array',
        'processed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\User::class, 'employee_id');
    }
}