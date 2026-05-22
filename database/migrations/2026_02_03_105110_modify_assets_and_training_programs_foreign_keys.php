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
        // Modify assets table
        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'asset_type_id')) {
            Schema::table('assets', function (Blueprint $table) {
                // Drop the existing foreign key constraint
                $table->dropForeign(['asset_type_id']);
            });
            
            Schema::table('assets', function (Blueprint $table) {
                // Modify the column to be nullable
                $table->unsignedBigInteger('asset_type_id')->nullable()->change();
            });
            
            Schema::table('assets', function (Blueprint $table) {
                // Add the foreign key constraint back with SET NULL on delete
                $table->foreign('asset_type_id')
                      ->references('id')
                      ->on('asset_types')
                      ->onDelete('set null');
            });
        }

        // Modify training_programs table
        if (Schema::hasTable('training_programs') && Schema::hasColumn('training_programs', 'training_type_id')) {
            Schema::table('training_programs', function (Blueprint $table) {
                // Drop the existing foreign key constraint
                $table->dropForeign(['training_type_id']);
            });
            
            Schema::table('training_programs', function (Blueprint $table) {
                // Modify the column to be nullable
                $table->unsignedBigInteger('training_type_id')->nullable()->change();
            });
            
            Schema::table('training_programs', function (Blueprint $table) {
                // Add the foreign key constraint back with SET NULL on delete
                $table->foreign('training_type_id')
                      ->references('id')
                      ->on('training_types')
                      ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert assets table
        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'asset_type_id')) {
            Schema::table('assets', function (Blueprint $table) {
                // Drop the foreign key constraint
                $table->dropForeign(['asset_type_id']);
            });
            
            Schema::table('assets', function (Blueprint $table) {
                // Modify the column back to not nullable
                $table->unsignedBigInteger('asset_type_id')->nullable(false)->change();
            });
            
            Schema::table('assets', function (Blueprint $table) {
                // Add the foreign key constraint back with RESTRICT on delete
                $table->foreign('asset_type_id')
                      ->references('id')
                      ->on('asset_types')
                      ->onDelete('restrict');
            });
        }

        // Revert training_programs table
        if (Schema::hasTable('training_programs') && Schema::hasColumn('training_programs', 'training_type_id')) {
            Schema::table('training_programs', function (Blueprint $table) {
                // Drop the foreign key constraint
                $table->dropForeign(['training_type_id']);
            });
            
            Schema::table('training_programs', function (Blueprint $table) {
                // Modify the column back to not nullable
                $table->unsignedBigInteger('training_type_id')->nullable(false)->change();
            });
            
            Schema::table('training_programs', function (Blueprint $table) {
                // Add the foreign key constraint back with RESTRICT on delete
                $table->foreign('training_type_id')
                      ->references('id')
                      ->on('training_types')
                      ->onDelete('restrict');
            });
        }
    }
};
