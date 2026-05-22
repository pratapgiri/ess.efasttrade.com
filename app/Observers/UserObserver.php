<?php

namespace App\Observers;

use App\Models\CompanyClaimConfig;
use App\Models\User;
use App\Models\Plan;
use App\Services\ClaimWorkflowService;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        // Only assign plans in SaaS mode
        if (isSaas() && $user->type === 'company' && is_null($user->plan_id)) {
            $defaultPlan = Plan::getDefaultPlan();
            if ($defaultPlan) {
                $user->plan_id = $defaultPlan->id;
                $user->plan_is_active = 1;
            }
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Generate a unique referral code only in SaaS mode
        if (isSaas() && $user->type === 'company' && empty($user->referral_code)) {
            do {
                $code = rand(100000, 999999);
            } while (User::where('referral_code', $code)->exists());

            //$user->referral_code = $code;
            //$user->save();

            // Avoid model save() here to prevent identity-column update attempts on SQL Server
            User::where('id', $user->id)->update([
            'referral_code' => $code,
            ]);
        }

        // Create default settings for new users
        if (isSaas() && $user->type === 'superadmin') {
            createDefaultSettings($user->id);
        } elseif ($user->type === 'company') {
            copySettingsFromSuperAdmin($user->id);
            CompanyClaimConfig::forCompany($user->id);
            app(ClaimWorkflowService::class)->seedDefaultWorkflows($user->id);
        }
    }
}
