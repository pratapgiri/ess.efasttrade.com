<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('event_key', 80)->index();
            $table->string('title');
            $table->text('message');
            $table->unsignedBigInteger('recipient_user_id')->nullable()->index();
            $table->string('recipient_email')->nullable();
            $table->string('status', 20)->default('queued')->index(); // sent|failed|skipped
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_notifications');
    }
};
