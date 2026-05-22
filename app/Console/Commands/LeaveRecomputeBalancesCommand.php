<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MonthlyPaidLeaveAccrualService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LeaveRecomputeBalancesCommand extends Command
{
    /**
     * Examples:
     *   php artisan leave:recompute-balances --dry-run
     *   php artisan leave:recompute-balances
     *   php artisan leave:recompute-balances --year=2026
     *   php artisan leave:recompute-balances --company-id=25 --year=2026
     *   php artisan leave:recompute-balances --employee-id=42
     *   php artisan leave:recompute-balances --floor-allocated
     */
    protected $signature = 'leave:recompute-balances
                            {--year= : Year to recompute (default: current calendar year)}
                            {--company-id= : Restrict to a single company owner user id}
                            {--employee-id= : Restrict to a single employee user id}
                            {--floor-allocated : Clamp negative allocated_days to 0 during recompute}
                            {--skip-clamp : Skip the clamp rebalance step (Remaining may stay negative)}
                            {--no-paid-only : Include unpaid leave types as well (default: paid only)}
                            {--dry-run : Preview only, no DB writes}
                            {--show-details : Print per-row diff after the summary}';

    protected $description = 'Rebuild leave_balances.used_days from the source-of-truth leave_applications table and stamp balance_deducted_at on counted approvals. By default, also strips stale [MONTH_END_LOP_CLAMP] adjustments and re-applies a fresh clamp so Remaining never lands negative (use --skip-clamp to opt out). Use --floor-allocated to clamp negative allocated_days at zero.';

    public function handle(MonthlyPaidLeaveAccrualService $service): int
    {
        $year = $this->option('year') !== null
            ? (int) $this->option('year')
            : (int) Carbon::now()->year;

        $isDryRun = (bool) $this->option('dry-run');
        $floorAllocated = (bool) $this->option('floor-allocated');
        $rebalanceClamps = ! (bool) $this->option('skip-clamp');
        $paidOnly = ! (bool) $this->option('no-paid-only');
        $showDetails = (bool) $this->option('show-details');

        $companyIdOpt = $this->option('company-id') ? (int) $this->option('company-id') : null;
        $employeeIdOpt = $this->option('employee-id') ? (int) $this->option('employee-id') : null;

        $this->info("Recomputing leave balances from leave_applications for year {$year}.");
        $this->info($isDryRun ? 'Mode: DRY RUN (no writes)' : 'Mode: LIVE');
        $this->line(sprintf(
            'Scope: %s | %s | floor_allocated=%s | rebalance_clamps=%s | paid_only=%s',
            $employeeIdOpt ? "employee_id={$employeeIdOpt}" : ($companyIdOpt ? "company_id={$companyIdOpt}" : 'all companies'),
            'year='.$year,
            $floorAllocated ? 'yes' : 'no',
            $rebalanceClamps ? 'yes' : 'no',
            $paidOnly ? 'yes' : 'no'
        ));

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
        $totalStamped = 0;
        $totalFloored = 0;
        $totalClampsStripped = 0;
        $totalClampStrippedDays = 0.0;
        $totalClampsApplied = 0;
        $totalClampAppliedDays = 0.0;
        $companiesErrored = 0;
        $allDetails = [];

        foreach ($companies as $company) {
            $companyUserIds = $this->getCompanyUserIds((int) $company->id);

            try {
                $result = $service->recomputeBalancesFromApplications(
                    $year,
                    $companyUserIds,
                    $employeeIdOpt,
                    $floorAllocated,
                    $paidOnly,
                    $isDryRun,
                    $rebalanceClamps
                );

                $totalScanned += $result['scanned'];
                $totalUpdated += $result['balances_updated'];
                $totalStamped += $result['applications_stamped'];
                $totalFloored += $result['allocated_floored'];
                $totalClampsStripped += $result['clamps_stripped'];
                $totalClampStrippedDays += $result['clamp_stripped_days'];
                $totalClampsApplied += $result['clamps_applied'];
                $totalClampAppliedDays += $result['clamp_applied_days'];

                $this->line(sprintf(
                    'Company #%d (%s): scanned %d, updated %d, stamped %d app(s), floored %d, clamps_stripped %d (%.2f d), clamps_applied %d (%.2f d).',
                    $company->id,
                    $company->name,
                    $result['scanned'],
                    $result['balances_updated'],
                    $result['applications_stamped'],
                    $result['allocated_floored'],
                    $result['clamps_stripped'],
                    $result['clamp_stripped_days'],
                    $result['clamps_applied'],
                    $result['clamp_applied_days']
                ));

                if ($showDetails) {
                    foreach ($result['details'] as $row) {
                        if (abs($row['used_delta']) < 0.005
                            && ! $row['allocated_floored']
                            && $row['applications_stamped'] === 0
                            && $row['clamps_stripped'] === 0
                            && ! $row['clamp_applied']
                        ) {
                            continue;
                        }
                        $allDetails[] = array_merge(
                            ['company_id' => (int) $company->id],
                            $row
                        );
                    }
                }
            } catch (\Throwable $e) {
                $companiesErrored++;
                $this->error(sprintf('Company #%d (%s) failed: %s', $company->id, $company->name, $e->getMessage()));
            }
        }

        if ($showDetails && ! empty($allDetails)) {
            $this->newLine();
            $this->info('Per-row diff (only rows with a change shown):');
            $this->table(
                ['Co', 'Emp', 'LT', 'Yr', 'Used→', 'Δ', 'Floor', 'Manual→', 'StripClamp', 'NewClamp', 'Stamp#'],
                array_map(function ($row) {
                    return [
                        $row['company_id'],
                        $row['employee_id'],
                        $row['leave_type_id'],
                        $row['year'],
                        sprintf('%s→%s', $row['used_before'], $row['used_after']),
                        sprintf('%+0.2f', $row['used_delta']),
                        $row['allocated_floored'] ? 'yes' : '-',
                        sprintf('%s→%s', $row['manual_before'], $row['manual_after']),
                        $row['clamps_stripped'] > 0
                            ? sprintf('%d × %.2f', $row['clamps_stripped'], $row['clamp_stripped_days'])
                            : '-',
                        $row['clamp_applied'] ? sprintf('+%.2f', $row['clamp_applied_days']) : '-',
                        $row['applications_stamped'],
                    ];
                }, $allDetails)
            );
        }

        $this->newLine();
        $this->info(sprintf(
            'Done for year %d. balances_scanned=%d updated=%d apps_stamped=%d allocated_floored=%d clamps_stripped=%d (%.2f d) clamps_applied=%d (%.2f d) errors=%d',
            $year,
            $totalScanned,
            $totalUpdated,
            $totalStamped,
            $totalFloored,
            $totalClampsStripped,
            $totalClampStrippedDays,
            $totalClampsApplied,
            $totalClampAppliedDays,
            $companiesErrored
        ));

        if ($isDryRun) {
            $this->warn('DRY-RUN: no writes were performed. Re-run without --dry-run to apply.');
        }

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
