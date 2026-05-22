<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MonthlyPaidLeaveAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LeaveYearEndResetCommand extends Command
{
    /**
     * Examples:
     *   php artisan leave:year-end-reset
     *   php artisan leave:year-end-reset --year=2026
     *   php artisan leave:year-end-reset --company-id=25
     *   php artisan leave:year-end-reset --dry-run
     */
    protected $signature = 'leave:year-end-reset
                            {--year= : Year to close out (default: current calendar year)}
                            {--company-id= : Restrict to a single company owner user id}
                            {--dry-run : Preview only, no DB writes}';

    protected $description = 'Year-end FULL WIPE: zeroes every paid-leave balance row in the chosen year (allocated, used, carried_forward, manual_adjustment and remaining all become 0). Next year balance rows are created fresh by the monthly accrual.';

    public function handle(MonthlyPaidLeaveAccrualService $service): int
    {
        $year = $this->option('year') !== null
            ? (int) $this->option('year')
            : (int) Carbon::now()->year;

        $isDryRun = (bool) $this->option('dry-run');
        $this->info("Running year-end reset for {$year}");
        $this->info($isDryRun ? 'Mode: DRY RUN (no writes)' : 'Mode: LIVE');

        $companyIdOpt = $this->option('company-id') ? (int) $this->option('company-id') : null;

        $companies = User::query()
            ->where('type', 'company')
            ->when($companyIdOpt, fn ($q) => $q->where('id', $companyIdOpt))
            ->get(['id', 'name']);

        if ($companies->isEmpty()) {
            $this->warn('No companies found in scope.');

            return self::SUCCESS;
        }

        $totalScanned = 0;
        $totalWiped = 0;
        $totalSkipped = 0;
        $companiesErrored = 0;

        if ($isDryRun) {
            DB::beginTransaction();
        }

        foreach ($companies as $company) {
            $companyUserIds = $this->getCompanyUserIds((int) $company->id);

            try {
                $result = $service->yearEndReset($year, $companyUserIds);
                $totalScanned += $result['scanned'];
                $totalWiped += $result['wiped'];
                $totalSkipped += $result['skipped'];

                $this->line(sprintf(
                    'Company #%d (%s): scanned %d, wiped %d, already-wiped %d.',
                    $company->id,
                    $company->name,
                    $result['scanned'],
                    $result['wiped'],
                    $result['skipped']
                ));
            } catch (\Throwable $e) {
                $companiesErrored++;
                $this->error(sprintf('Company #%d (%s) failed: %s', $company->id, $company->name, $e->getMessage()));
            }
        }

        if ($isDryRun) {
            DB::rollBack();
            $this->warn('DRY-RUN: rolled back all writes.');
        }

        $this->newLine();
        $this->info(sprintf(
            'Done for year %d. Balance rows scanned: %d, wiped: %d, already-wiped: %d, errors: %d',
            $year,
            $totalScanned,
            $totalWiped,
            $totalSkipped,
            $companiesErrored
        ));

        return $companiesErrored > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int,int>
     */
    private function getCompanyUserIds(int $companyId): array
    {
        $ids = [$companyId];

        $stack = [$companyId];
        while (! empty($stack)) {
            $parent = array_pop($stack);
            $children = User::query()->where('created_by', $parent)->pluck('id')->all();
            foreach ($children as $childId) {
                if (! in_array((int) $childId, $ids, true)) {
                    $ids[] = (int) $childId;
                    $stack[] = (int) $childId;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
