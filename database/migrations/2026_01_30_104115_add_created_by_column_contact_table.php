<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('contacts')) {
            Schema::table('contacts', function (Blueprint $table) {
                if (!Schema::hasColumn('contacts', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('message')->constrained('users')->onDelete('cascade');
                }
                if (!Schema::hasColumn('contacts', 'status')) {
                    $table->enum('status', ['New', 'Contacted', 'Qualified', 'Converted', 'Closed'])->default('New')->after('created_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('contacts')) {
            Schema::table('contacts', function (Blueprint $table) {
                if (Schema::hasColumn('contacts', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('contacts', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};
