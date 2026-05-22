<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkFromHomeRequest extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'reason',
        'attachment',
        'designation',
        'department',
        'status',
        'manager_comments',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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

    public function createAttendanceRecords(): void
    {
        if ($this->status !== 'approved') {
            return;
        }

        $startDate = $this->start_date->copy();
        $endDate = $this->end_date->copy();

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            $record = AttendanceRecord::where('employee_id', $this->employee_id)
                ->whereDate('date', $date->toDateString())
                ->first();

            if (! $record) {
                AttendanceRecord::create([
                    'employee_id' => $this->employee_id,
                    'date' => $date->toDateString(),
                    'status' => 'on_leave',
                    'is_absent' => false,
                    'total_hours' => 0,
                    'notes' => 'WFH',
                    'created_by' => $this->created_by,
                ]);
            } else {
                $record->update([
                    'status' => 'on_leave',
                    'notes' => 'WFH',
                ]);
            }
        }
    }
}
