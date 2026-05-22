<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Late penalty is not scheduled; company admins run it from HR → Late Penalty Logs → "Calculate penalty".
        // Optional: `php artisan attendance:apply-late-penalty` (e.g. with --year, --month, --company-id).

        // Monthly leave accrual — runs on the 1st of every month at 00:05 server time.
        // Credits a flat 2 days/month for every paid leave type, pro-rated for
        // employees who joined inside the month being processed, and clamps
        // any negative closing balance to zero. Defaults to the previous
        // calendar month, so running on Apr 1 processes March.
        $schedule->command('leave:monthly-accrual')
            ->monthlyOn(1, '00:05')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();

        // Year-end reset — runs on Dec 31 at 23:55 server time.
        // Ensures every paid leave balance for the closing year ends at
        // remaining_days >= 0 (negatives settled as LWP via manual_adjustment).
        // New-year rows are created on next monthly run with carried_forward = 0,
        // so no leave rolls over by default.
        $schedule->command('leave:year-end-reset')
            ->cron('55 23 31 12 *')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
