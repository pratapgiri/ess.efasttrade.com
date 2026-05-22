<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Models\MonthlyPlAccrualLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MonthlyPlAccrualLogController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::user()->can('manage-leave-balances')) {
            return redirect()->route('hr.leave-balances.index')->with('error', __('Permission Denied.'));
        }

        $viewer = $request->user();

        $query = MonthlyPlAccrualLog::query()
            ->with([
                'employee:id,name,email',
                'leaveType:id,name,color',
                'processor:id,name',
            ])
            ->orderByDesc('processed_at')
            ->orderByDesc('id');

        if ($viewer->isSuperAdmin()) {
            if ($request->filled('company_id')) {
                $query->where('company_id', (int) $request->company_id);
            }
        } else {
            $companyScopeId = $viewer->type === 'company'
                ? (int) $viewer->id
                : (int) ($viewer->created_by ?: $viewer->id);

            $query->where('company_id', $companyScopeId);
        }

        if ($request->filled('year') && $request->year !== 'all') {
            $query->where('year', (int) $request->year);
        }
        if ($request->filled('month') && $request->month !== 'all') {
            $query->where('month', (int) $request->month);
        }
        if ($request->filled('leave_type_id') && $request->leave_type_id !== 'all') {
            $query->where('leave_type_id', (int) $request->leave_type_id);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('batch_id') && $request->batch_id !== '') {
            $query->where('batch_id', $request->batch_id);
        }
        if ($request->filled('search') && $request->search !== '') {
            $term = '%'.$request->search.'%';
            $query->whereHas('employee', function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        $logs = $query->paginate($request->integer('per_page', 20))->withQueryString();

        $companies = User::where('type', 'company')->orderBy('name')->get(['id', 'name']);

        if ($viewer->isSuperAdmin()) {
            $leaveTypes = $request->filled('company_id')
                ? LeaveType::query()
                    ->where('created_by', (int) $request->company_id)
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : collect();
        } else {
            $leaveTypes = LeaveType::whereIn('created_by', getCompanyAndUsersId())
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return Inertia::render('hr/monthly-pl-accrual-logs/index', [
            'logs' => $logs,
            'companies' => $companies,
            'leaveTypes' => $leaveTypes,
            'filters' => $request->only(['company_id', 'year', 'month', 'leave_type_id', 'status', 'batch_id', 'search', 'per_page']),
        ]);
    }
}
