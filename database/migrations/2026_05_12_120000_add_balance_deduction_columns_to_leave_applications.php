<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a "balance_deducted_at" stamp to leave_applications so the
     * deduction from leave_balances.used_days can be made idempotent: a
     * second approval (re-approve, re-save, etc.) detects the stamp and
     * skips re-deducting. The same stamp is cleared when a status change,
     * destroy, or total_days edit reverts the deduction.
     */
    public function up(): void
    {
        if (! Schema::hasTable('leave_applications')) {
            return;
        }

        Schema::table('leave_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_applications', 'balance_deducted_at')) {
                $table->timestamp('balance_deducted_at')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('leave_applications', 'balance_deducted_days')) {
                // Snapshot of total_days at the time of deduction. Lets us
                // reverse the exact number even if total_days was edited
                // before the reversal runs.
                $table->decimal('balance_deducted_days', 8, 2)->nullable()->after('balance_deducted_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leave_applications')) {
            return;
        }

        Schema::table('leave_applications', function (Blueprint $table) {
            if (Schema::hasColumn('leave_applications', 'balance_deducted_days')) {
                $table->dropColumn('balance_deducted_days');
            }
            if (Schema::hasColumn('leave_applications', 'balance_deducted_at')) {
                $table->dropColumn('balance_deducted_at');
            }
        });
    }
};
