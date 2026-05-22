<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MonthlyPaidLeaveAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time cleanup command. The HRMS used to carry leftover days from one
 * year into the next via the `carried_forward` column. Under the new policy
 * (annual reset on Dec 31, no rollover) those values are stale and must be
 * zeroed across every paid-leave balance row for the active year.
 *
 * Examples:
 *   php artisan leave:reset-carryforward --dry-run
 *   php artisan leave:reset-carryforward --year=2026
 *   php artisan leave:reset-carryforward --company-id=18 --year=2026
 */
class LeaveResetCarryForwardCommand extends Command
{
    protected $signature = 'leave:reset-carryforward
                            {--year= : Year whose carry-forward values should be zeroed (default: current calendar year)}
                            {--company-id= : Restrict to a single company owner user id}
                            {--dry-run : Preview only, no DB writes}';

    protected $description = 'One-time cleanup: zeroes carried_forward (and recomputes remaining_days) for every paid-leave balance row in the chosen year.';

    public function handle(MonthlyPaidLeaveAccrualService $service): int
    {
        $year = $this->option('year') !== null
            ? (int) $this->option('year')
            : (int) Carbon::now()->year;

        $isDryRun = (bool) $this->option('dry-run');
        $this->info("Resetting carry-forward for {$year}");
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
        $totalUpdated = 0;
        $companiesErrored = 0;

        if ($isDryRun) {
            DB::beginTransaction();
        }

        foreach ($companies as $company) {
            $companyUserIds = $this->getCompanyUserIds((int) $company->id);

            try {
                $result = $service->resetCarryForward($year, $companyUserIds);
                $totalScanned += $result['scanned'];
                $totalUpdated += $result['updated'];

                $this->line(sprintf(
                    'Company #%d (%s): scanned %d, updated %d.',
                    $company->id,
                    $company->name,
                    $result['scanned'],
                    $result['updated']
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
            'Done for year %d. Balance rows scanned: %d, updated: %d, errors: %d',
            $year,
            $totalScanned,
            $totalUpdated,
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
