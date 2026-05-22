<?php

namespace App\Http\Controllers;

use App\Models\LatePenaltyLog;
use App\Models\User;
use App\Services\LatePenaltyApplicator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LatePenaltyLogController extends Controller
{
    protected function ensureCompanyOrSuperAdmin(Request $request): void
    {
        $viewer = $request->user();
        if (! $viewer || (! $viewer->isSuperAdmin() && $viewer->type !== 'company')) {
            abort(403, __('Only company accounts can access late penalty logs.'));
        }
    }

    public function index(Request $request)
    {
        $this->ensureCompanyOrSuperAdmin($request);

        $query = LatePenaltyLog::query()
            ->with(['employee:id,name,email']) // if relation exists in model
            ->orderByDesc('id');

        // Scope by company if not superadmin.
        // Company users should see their own company logs (company_id = user id).
        // Employee/other users should map to their parent company (created_by).
        if (! auth()->user()->isSuperAdmin()) {
            $viewer = auth()->user();
            $companyScopeId = $viewer->type === 'company'
                ? (int) $viewer->id
                : (int) ($viewer->created_by ?: $viewer->id);

            $query->where('company_id', $companyScopeId);
        }

        // Filters
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('year')) {
            $query->where('year', (int) $request->year);
        }
        if ($request->filled('month')) {
            $query->where('month', (int) $request->month);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate($request->integer('per_page', 20))->withQueryString();

        $companies = User::where('type', 'company')->get(['id', 'name']);

        return Inertia::render('hr/late-penalty-logs/index', [
            'logs' => $logs,
            'companies' => $companies,
            'filters' => $request->only(['company_id', 'year', 'month', 'status', 'per_page']),
        ]);
    }

    /**
     * Run the same late-penalty job as the scheduled artisan command, scoped to one company.
     */
    public function calculate(Request $request, LatePenaltyApplicator $applicator)
    {
        $this->ensureCompanyOrSuperAdmin($request);

        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'company_id' => 'nullable|integer',
        ]);

        $viewer = $request->user();

        if ($viewer->isSuperAdmin()) {
            $companyId = (int) $request->input('company_id');
            if ($companyId <= 0) {
                return redirect()->back()->with('error', __('Please select a company.'));
            }
            if (! User::where('id', $companyId)->where('type', 'company')->exists()) {
                return redirect()->back()->with('error', __('Invalid company.'));
            }
        } else {
            $companyId = $viewer->type === 'company'
                ? (int) $viewer->id
                : (int) ($viewer->created_by ?? 0);
            if ($companyId <= 0) {
                return redirect()->back()->with('error', __('Unable to determine your company.'));
            }
        }

        try {
            $result = $applicator->run(
                (int) $request->input('year'),
                (int) $request->input('month'),
                $companyId,
                (int) $viewer->id,
                false
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($result['no_employees']) {
            return redirect()->back()->with('error', __('No active employees found for the selected company.'));
        }

        $message = __(
            'Late penalty completed for :month. Processed: :processed, penalized: :penalized, skipped: :skipped, errors: :errors.',
            [
                'month' => $result['month_key'],
                'processed' => $result['processed'],
                'penalized' => $result['penalized'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
            ]
        );

        if ($result['errors'] > 0) {
            return redirect()->back()->with('error', $message);
        }

        return redirect()->back()->with('success', $message);
    }
}