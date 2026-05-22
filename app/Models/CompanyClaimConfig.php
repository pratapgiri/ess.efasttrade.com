<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyClaimConfig extends Model
{
    protected $fillable = [
        'company_id',
        'expense_enabled',
        'conveyance_enabled',
        'travel_enabled',
        'expense_current_month_only',
        'created_by',
    ];

    protected $casts = [
        'expense_enabled' => 'boolean',
        'conveyance_enabled' => 'boolean',
        'travel_enabled' => 'boolean',
        'expense_current_month_only' => 'boolean',
    ];

    public static function forCompany(int $companyId): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId],
            [
                'expense_enabled' => true,
                'conveyance_enabled' => true,
                'travel_enabled' => true,
                'expense_current_month_only' => true,
                'created_by' => $companyId,
            ]
        );
    }

    public function toVisibilityArray(): array
    {
        return [
            'EXPENSES_AVAILABLE' => $this->expense_enabled,
            'LOCAL_CONVEYANCE_AVAILABLE' => $this->conveyance_enabled,
            'INTERCITY_CONVEYANCE_AVAILABLE' => $this->travel_enabled,
            'ALLOW_EXPENSES_CURRENT_MONTH_ONLY' => $this->expense_current_month_only,
        ];
    }
}
