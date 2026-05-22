<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Support half-day penalties (0.5) for late deduction.
        DB::statement('ALTER TABLE leave_applications ALTER COLUMN total_days DECIMAL(8,2) NOT NULL');
    }

    public function down(): void
    {
        // Revert to integer days if rollback is needed.
        DB::statement('ALTER TABLE leave_applications ALTER COLUMN total_days INT NOT NULL');
    }
};
