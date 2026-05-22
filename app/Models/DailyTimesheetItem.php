<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyTimesheetItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'daily_timesheet_id',
        'line_no',
        'from_time',
        'to_time',
        'task_note',
    ];

    public function timesheet()
    {
        return $this->belongsTo(DailyTimesheet::class, 'daily_timesheet_id');
    }
}

