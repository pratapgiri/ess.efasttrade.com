<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimWorkflow extends Model
{
    protected $fillable = [
        'company_id',
        'route_name',
        'claim_type',
        'serial_number',
        'role_type',
        'approver_user_id',
        'department_id',
        'status',
        'created_by',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
