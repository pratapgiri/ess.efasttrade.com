<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No FKs to users/leave_types (same rationale as late_penalty_logs + leave_balance_monthly_accruals).
        Schema::create('monthly_pl_accrual_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('working_days', 8, 2)->default(0);
            $table->unsignedTinyInteger('entitled_days')->default(0);
            $table->decimal('previous_credited_days', 8, 2)->default(0);
            $table->decimal('delta_applied', 8, 2)->default(0);
            $table->decimal('allocated_after', 8, 2)->nullable();
            $table->string('status', 32);
            $table->text('message')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();

            $table->index(['company_id', 'year', 'month']);
            $table->index(['batch_id']);
            $table->index(['employee_id', 'year', 'month']);
            $table->index(['status', 'processed_at']);
            $table->index('leave_type_id');
            $table->index('processed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_pl_accrual_logs');
    }
};
