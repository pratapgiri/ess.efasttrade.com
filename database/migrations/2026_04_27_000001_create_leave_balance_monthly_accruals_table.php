<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Intentionally no FKs to users/leave_types (matches late_penalty_logs): SQL Server and
        // some deployments do not expose a compatible PK on users for Laravel foreignId().
        Schema::create('leave_balance_monthly_accruals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('working_days', 8, 2)->default(0);
            $table->decimal('days_accrued', 8, 2)->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year', 'month'], 'lbma_employee_leave_ym_unique');
            $table->index('employee_id');
            $table->index('leave_type_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_monthly_accruals');
    }
};
