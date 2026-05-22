<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('late_penalty_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('leave_type_id')->nullable();
            $table->unsignedBigInteger('leave_application_id')->nullable();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            $table->unsignedInteger('late_count')->default(0);
            $table->decimal('expected_penalty_days', 6, 2)->default(0);
            $table->decimal('already_applied_days', 6, 2)->default(0);
            $table->decimal('applied_now_days', 6, 2)->default(0);

            $table->string('status', 30); // applied | skipped | dry_run | failed
            $table->text('message')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();

            $table->index(['company_id', 'year', 'month']);
            $table->index(['employee_id', 'year', 'month']);
            $table->index(['status', 'processed_at']);
            $table->index(['leave_application_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_penalty_logs');
    }
};