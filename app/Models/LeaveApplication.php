<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'leave_policy_id',
        'start_date',
        'end_date',
        'total_days',
        'half_day_part',
        'reason',
        'attachment',
        'status',
        'manager_comments',
        'approved_by',
        'approved_at',
        'balance_deducted_at',
        'balance_deducted_days',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'balance_deducted_at' => 'datetime',
        'balance_deducted_days' => 'decimal:2',
    ];

    /**
     * Get the employee who applied for leave.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the leave type.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the leave policy.
     */
    public function leavePolicy()
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    /**
     * Get the manager who approved/rejected.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who created the application.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Notes stored on attendance when leave is approved (includes half-day part when applicable).
     */
    public function buildLeaveAttendanceNotes(): string
    {
        $base = 'Leave: '.$this->leaveType->name;

        if ((float) $this->total_days !== 0.5) {
            return $base;
        }

        $partLabel = $this->half_day_part === 'second_half' ? 'Second half' : 'First half';

        return $base.' (Half Day — '.$partLabel.')';
    }

    /**
     * Create attendance records and update leave balance when leave is approved.
     */
    public function createAttendanceRecords()
    {
        if ($this->status === 'approved') {
            $startDate = $this->start_date;
            $endDate = $this->end_date;
            
            // Loop through each day of leave
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                // Skip weekends (optional - depends on company policy)
                if ($date->isWeekend()) {
                    continue;
                }
                
                // Check if attendance record already exists
                $existingRecord = \App\Models\AttendanceRecord::where('employee_id', $this->employee_id)
                    ->where('date', $date->format('Y-m-d'))
                    ->first();
                
                if (!$existingRecord) {
                    \App\Models\AttendanceRecord::create([
                        'employee_id' => $this->employee_id,
                        'date' => $date->format('Y-m-d'),
                        'status' => ((float) $this->total_days === 0.5 ? 'half_day' : 'on_leave'),
                        'is_absent' => false,
                        'total_hours' => 0,
                        'notes' => $this->buildLeaveAttendanceNotes(),
                        'created_by' => $this->created_by,
                    ]);
                } else {
                    // Update existing record to on_leave
                    $existingRecord->update([
                        'status' => ((float) $this->total_days === 0.5 ? 'half_day' : 'on_leave'),
                        'notes' => $this->buildLeaveAttendanceNotes(),
                    ]);
                }
            }
            
            // Update leave balance - deduct used days
            $this->updateLeaveBalance();
        }
    }
    
    /**
     * Year associated with this leave (based on start_date, falls back to now()).
     */
    public function balanceYear(): int
    {
        $start = $this->start_date;

        if ($start instanceof \Carbon\CarbonInterface) {
            return (int) $start->year;
        }

        if (is_string($start) && $start !== '') {
            try {
                return (int) \Carbon\Carbon::parse($start)->year;
            } catch (\Throwable $e) {
                // fall through to now()
            }
        }

        return (int) now()->year;
    }

    /**
     * Resolve (or create) the LeaveBalance row this application affects.
     */
    public function resolveLeaveBalance(): \App\Models\LeaveBalance
    {
        $year = $this->balanceYear();

        $defaultAllocated = 0;
        if ($this->relationLoaded('leavePolicy') || $this->leave_policy_id) {
            $policy = $this->leavePolicy;
            if ($policy && isset($policy->max_days_per_year)) {
                $defaultAllocated = (float) $policy->max_days_per_year;
            }
        }

        return \App\Models\LeaveBalance::firstOrCreate(
            [
                'employee_id' => $this->employee_id,
                'leave_type_id' => $this->leave_type_id,
                'year' => $year,
            ],
            [
                'leave_policy_id' => $this->leave_policy_id,
                'allocated_days' => $defaultAllocated,
                'used_days' => 0,
                'remaining_days' => $defaultAllocated,
                'created_by' => $this->created_by,
            ]
        );
    }

    /**
     * Apply this leave's days to the balance's used_days. Idempotent: if the
     * application already has a balance_deducted_at stamp, no-op. This is the
     * single source of truth for "this leave has been counted as used".
     *
     * @return bool true if a deduction was applied, false if it was a no-op
     */
    public function applyToBalance(): bool
    {
        if ($this->balance_deducted_at !== null) {
            return false;
        }

        $balance = $this->resolveLeaveBalance();
        $daysToDeduct = round((float) $this->total_days, 2);

        if ($daysToDeduct <= 0) {
            return false;
        }

        $availableBefore = max(0, (float) $balance->remaining_days);
        $lwpDays = max(0, round($daysToDeduct - $availableBefore, 2));

        $balance->used_days = round((float) $balance->used_days + $daysToDeduct, 2);
        $balance->remaining_days = $this->recomputeRemaining($balance);
        $balance->save();

        $this->balance_deducted_at = now();
        $this->balance_deducted_days = $daysToDeduct;

        // Persist LWP marker so payroll can deduct overdrawn part.
        if ($lwpDays > 0) {
            $existingComment = (string) ($this->manager_comments ?? '');
            if (! preg_match('/\[LWP_DAYS:[0-9]+(?:\.[0-9]+)?\]/', $existingComment)) {
                $lwpTag = "[LWP_DAYS:{$lwpDays}]";
                $this->manager_comments = trim($existingComment . ' ' . $lwpTag . ' Auto-marked as Leave Without Pay due to negative leave balance.');
            }
        }

        $this->save();

        return true;
    }

    /**
     * Reverse a previously-applied deduction. Idempotent: if no deduction is
     * recorded (balance_deducted_at is null), no-op. Always reverses the
     * snapshotted balance_deducted_days, never the current total_days, so
     * mid-flight edits remain consistent.
     *
     * @return bool true if a reversal was applied, false if it was a no-op
     */
    public function revertFromBalance(): bool
    {
        if ($this->balance_deducted_at === null) {
            return false;
        }

        $balance = $this->resolveLeaveBalance();
        $daysToReturn = round((float) ($this->balance_deducted_days ?? $this->total_days), 2);

        if ($daysToReturn > 0) {
            $balance->used_days = round(max(0, (float) $balance->used_days - $daysToReturn), 2);
            $balance->remaining_days = $this->recomputeRemaining($balance);
            $balance->save();
        }

        $this->balance_deducted_at = null;
        $this->balance_deducted_days = null;

        // Strip any auto-added LWP marker; this leave no longer contributes to LOP.
        $existingComment = (string) ($this->manager_comments ?? '');
        if ($existingComment !== '') {
            $cleaned = preg_replace(
                '/\s*\[LWP_DAYS:[0-9]+(?:\.[0-9]+)?\]\s*Auto-marked as Leave Without Pay due to negative leave balance\.?\s*/i',
                ' ',
                $existingComment
            );
            $cleaned = trim(preg_replace('/\s+/', ' ', (string) $cleaned));
            if ($cleaned !== $existingComment) {
                $this->manager_comments = $cleaned !== '' ? $cleaned : null;
            }
        }

        $this->save();

        return true;
    }

    /**
     * Mark this application as already-deducted without touching the balance.
     * Used by the recompute command after it rebuilds used_days from source,
     * so future status changes flow through applyToBalance / revertFromBalance
     * correctly.
     */
    public function markBalanceDeducted(?float $days = null, ?\Carbon\CarbonInterface $at = null): void
    {
        $this->balance_deducted_at = $at ?? now();
        $this->balance_deducted_days = round((float) ($days ?? $this->total_days), 2);
        $this->save();
    }

    private function recomputeRemaining(\App\Models\LeaveBalance $balance): float
    {
        return round(
            ((float) $balance->allocated_days + (float) $balance->carried_forward + (float) $balance->manual_adjustment)
            - (float) $balance->used_days,
            2
        );
    }

    /**
     * Backward-compatible wrapper. Old call sites continue to work but now
     * benefit from idempotency via applyToBalance().
     *
     * @deprecated Use applyToBalance() / revertFromBalance() instead.
     */
    public function updateLeaveBalance()
    {
        $this->applyToBalance();
    }
}