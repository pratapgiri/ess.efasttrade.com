<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('claim_workflows')) {
            return;
        }

        Schema::create('company_claim_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->boolean('expense_enabled')->default(true);
            $table->boolean('conveyance_enabled')->default(true);
            $table->boolean('travel_enabled')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique('company_id');
        });

        Schema::create('claim_workflows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('route_name', 64);
            $table->string('claim_type', 32);
            $table->unsignedTinyInteger('serial_number');
            $table->string('role_type', 32);
            $table->unsignedBigInteger('approver_user_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'claim_type', 'route_name']);
            $table->unique(['company_id', 'claim_type', 'route_name', 'serial_number'], 'claim_workflows_level_unique');
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('claim_type', 32);
            $table->string('claim_no', 64)->index();
            $table->date('claim_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('pending_user_id')->nullable()->index();
            $table->unsignedTinyInteger('current_workflow_level')->default(1);
            $table->string('route_name', 64)->nullable();
            $table->string('pending_role_type', 32)->nullable();
            $table->text('narration')->nullable();
            $table->text('employee_remark')->nullable();
            $table->text('manager_remark')->nullable();
            $table->text('final_remarks')->nullable();
            $table->decimal('passed_amount', 15, 2)->nullable();
            $table->string('bill_no', 128)->nullable();
            $table->date('bill_date')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('pending_since')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'claim_date']);
            $table->index(['company_id', 'status', 'claim_type']);
        });

        Schema::create('claim_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_id')->index();
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('claim_id')->references('id')->on('claims')->cascadeOnDelete();
        });

        Schema::create('claim_workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_id')->index();
            $table->unsignedTinyInteger('workflow_level')->nullable();
            $table->string('action', 32);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('action_by')->nullable();
            $table->string('from_pending_role', 32)->nullable();
            $table->string('to_pending_role', 32)->nullable();
            $table->unsignedBigInteger('from_pending_user_id')->nullable();
            $table->unsignedBigInteger('to_pending_user_id')->nullable();
            $table->timestamp('action_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('claim_id')->references('id')->on('claims')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_workflow_logs');
        Schema::dropIfExists('claim_attachments');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('claim_workflows');
        Schema::dropIfExists('company_claim_configs');
    }
};
