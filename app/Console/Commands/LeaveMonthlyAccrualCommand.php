<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MonthlyPaidLeaveAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LeaveMonthlyAccrualCommand extends Command
{
    /**
     * Examples:
     *   php artisan leave:monthly-accrual
     *   php artisan leave:monthly-accrual --year=2026 --month=4
     *   php artisan leave:monthly-accrual --company-id=25
     *   php artisan leave:monthly-accrual --dry-run
     */
    protected $signature = 'leave:monthly-accrual
                            {--year= : Year to process (default: current calendar year)}
                            {--month= : Month to process 1-12 (default: current calendar month)}
                            {--company-id= : Restrict to a single company owner user id}
                            {--dry-run : Preview only, no DB writes}';

    protected $description = 'Credit the monthly leave accrual (flat 2 days/month, pro-rated for new joiners) for every paid leave type, and clamp negative closing balances to zero. Default scope is the CURRENT month so December gets credited before the Dec 31 year-end wipe.';

    public function handle(MonthlyPaidLeaveAccrualService $service): int
    {
        [$year, $month] = $this->resolveMonth();
        $monthKey = sprintf('%04d-%02d', $year, $month);
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Running monthly leave accrual for {$monthKey}");
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

        $totalProcessed = 0;
        $totalClamped = 0;
        $companiesHandled = 0;
        $companiesErrored = 0;

        if ($isDryRun) {
            DB::beginTransaction();
        }

        foreach ($companies as $company) {
            $companyUserIds = $this->getCompanyUserIds((int) $company->id);

            try {
                $result = $service->processCompanyMonth(
                    $year,
                    $month,
                    (int) $company->id,
                    $companyUserIds,
                    (int) $company->id,
                    (int) $company->id
                );

                $companiesHandled++;
                $totalProcessed += $result['processed'];
                $totalClamped += $result['clamped'];

                $this->line(sprintf(
                    'Company #%d (%s): %d leave type(s), %d employee accrual(s), %d clamp(s), %d skipped after year-end wipe.',
                    $company->id,
                    $company->name,
                    $result['leave_types'],
                    $result['processed'],
                    $result['clamped'],
                    $result['skipped_after_wipe']
                ));
            } catch (\InvalidArgumentException $e) {
                // No active paid policy / inactive type / future month — just skip.
                $this->warn(sprintf('Company #%d (%s) skipped: %s', $company->id, $company->name, $e->getMessage()));
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
            'Done for %s. Companies: %d, employees credited: %d, balances clamped: %d, errors: %d',
            $monthKey,
            $companiesHandled,
            $totalProcessed,
            $totalClamped,
            $companiesErrored
        ));

        return $companiesErrored > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function resolveMonth(): array
    {
        $optYear = $this->option('year');
        $optMonth = $this->option('month');

        if ($optYear !== null && $optMonth !== null) {
            $year = (int) $optYear;
            $month = (int) $optMonth;
            if ($month < 1 || $month > 12) {
                throw new \InvalidArgumentException('Invalid month, must be 1-12.');
            }

            return [$year, $month];
        }

        // Default to CURRENT month so December's accrual is in place before
        // the Dec 31 23:55 year-end wipe. Admin can pass --year/--month to
        // back-fill any earlier month explicitly.
        $now = Carbon::now();

        return [(int) $now->year, (int) $now->month];
    }

    /**
     * Return the company-id plus every user descended from it. Mirrors the
     * tenant scope used elsewhere (see helper getCompanyAndUsersId).
     *
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
