<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_claim_configs')) {
            return;
        }

        if (Schema::hasColumn('company_claim_configs', 'expense_current_month_only')) {
            return;
        }

        Schema::table('company_claim_configs', function (Blueprint $table) {
            $table->boolean('expense_current_month_only')->default(true)->after('travel_enabled');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('company_claim_configs') && Schema::hasColumn('company_claim_configs', 'expense_current_month_only')) {
            Schema::table('company_claim_configs', function (Blueprint $table) {
                $table->dropColumn('expense_current_month_only');
            });
        }
    }
};
