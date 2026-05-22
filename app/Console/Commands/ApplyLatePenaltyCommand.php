<?php

namespace App\Console\Commands;

use App\Services\LatePenaltyApplicator;
use Illuminate\Console\Command;

class ApplyLatePenaltyCommand extends Command
{
    /**
     * Examples:
     * php artisan attendance:apply-late-penalty --dry-run
     * php artisan attendance:apply-late-penalty --company-id=25 --dry-run
     * php artisan attendance:apply-late-penalty --year=2026 --month=4 --company-id=25
     */
    protected $signature = 'attendance:apply-late-penalty
                            {--year= : Year to process (default: previous month year)}
                            {--month= : Month to process 1-12 (default: previous month)}
                            {--company-id= : Apply only for this company owner user id}
                            {--leave-type=Paid Leave : Leave type name used for penalty}
                            {--system-user-id=1 : User ID to set in approved_by}
                            {--dry-run : Preview only, no DB writes}';

    protected $description = 'Apply monthly late-arrival penalty: 10-minute grace; >10 to +30 mins counts as late (first 2 free, from 3rd +0.5), and >30 mins adds immediate 0.5 day';

    public function handle(LatePenaltyApplicator $applicator): int
    {
        $optYear = $this->option('year');
        $optMonth = $this->option('month');

        try {
            [$year, $month] = LatePenaltyApplicator::resolveMonthWindow(
                $optYear !== null ? (int) $optYear : null,
                $optMonth !== null ? (int) $optMonth : null
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $startOfMonth = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $monthKey = $startOfMonth->format('Y-m');
        $companyId = $this->option('company-id') ? (int) $this->option('company-id') : null;
        $leaveTypeName = (string) $this->option('leave-type');
        $systemUserId = (int) $this->option('system-user-id');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Running late penalty for {$monthKey}");
        $this->info("Leave type: {$leaveTypeName}");
        $this->info($companyId ? "Company scope: {$companyId}" : 'Company scope: ALL');
        $this->info($isDryRun ? 'Mode: DRY RUN (no writes)' : 'Mode: LIVE');

        $result = $applicator->run(
            $optYear !== null ? (int) $optYear : null,
            $optMonth !== null ? (int) $optMonth : null,
            $companyId,
            $systemUserId,
            $isDryRun,
            $leaveTypeName
        );

        if ($result['no_employees']) {
            $this->warn('No active employees found for selected scope.');

            return self::SUCCESS;
        }

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        foreach ($result['info_lines'] as $line) {
            if (str_starts_with($line, 'DRY-RUN')) {
                $this->line($line);
            } else {
                $this->info($line);
            }
        }

        $this->newLine();
        $this->info("Done for {$result['month_key']}");
        $this->line("Processed: {$result['processed']}");
        $this->line("Penalized: {$result['penalized']}");
        $this->line("Skipped:   {$result['skipped']}");
        $this->line("Errors:    {$result['errors']}");

        return $result['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
