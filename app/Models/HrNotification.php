<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class HrNotification extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'event_key',
        'title',
        'message',
        'recipient_user_id',
        'recipient_email',
        'status',
        'error_message',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
