<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
     protected $fillable = [
        'user_id',
        'ip',
        'date',
        'Details',
        'created_by'
    ];

    protected $casts = [
        'date' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
