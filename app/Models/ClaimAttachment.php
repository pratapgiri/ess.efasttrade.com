<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimAttachment extends Model
{
    protected $fillable = [
        'claim_id',
        'file_name',
        'file_path',
        'created_by',
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}
