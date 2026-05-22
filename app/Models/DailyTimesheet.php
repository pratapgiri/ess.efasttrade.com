<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyTimesheet extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'work_mode',
        'overall_note',
        'status',
        'submitted_at',
        'approved_by',
        'approved_at',
        'manager_comments',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(DailyTimesheetItem::class)->orderBy('line_no');
    }
}

