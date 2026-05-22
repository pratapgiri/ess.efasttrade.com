<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen monthly_pl_accrual_logs.entitled_days to decimal(8,2) so that
     * pro-rated accruals for mid-month joiners (e.g. 0.8 days) can be stored.
     */
    public function up(): void
    {
        if (! Schema::hasTable('monthly_pl_accrual_logs')) {
            return;
        }

        Schema::table('monthly_pl_accrual_logs', function (Blueprint $table) {
            $table->decimal('entitled_days', 8, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('monthly_pl_accrual_logs')) {
            return;
        }

        Schema::table('monthly_pl_accrual_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('entitled_days')->default(0)->change();
        });
    }
};
