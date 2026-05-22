<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimWorkflowLog extends Model
{
    protected $fillable = [
        'claim_id',
        'workflow_level',
        'action',
        'remarks',
        'action_by',
        'from_pending_role',
        'to_pending_role',
        'from_pending_user_id',
        'to_pending_user_id',
        'action_date',
        'created_by',
    ];

    protected $casts = [
        'action_date' => 'datetime',
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
