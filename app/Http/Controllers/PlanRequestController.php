<?php

namespace App\Http\Controllers;

use App\Models\PlanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PlanRequestController extends BaseController
{
    public function index(Request $request)
    {
        $query = PlanRequest::with(['user', 'plan', 'approver', 'rejector']);
        
        // For Company
        if (Auth::user()->hasRole('company')) {
            $query->where('user_id', Auth::user()->id);
        }

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('plan', function ($planQuery) use ($search) {
                    $planQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Handle status filter
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Handle date range filter
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Handle sorting
        $sortField = $request->get('sort_field', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validate sort field
        $allowedSortFields = ['id', 'created_at', 'status'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'id';
        }
        
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 10);
        $planRequests = $query->paginate($perPage);

        // Transform data for frontend
        $planRequests->getCollection()->transform(function ($planRequest) {
            return [
                'id' => $planRequest->id,
                'user' => [
                    'id' => $planRequest->user->id,
                    'name' => $planRequest->user->name,
                    'email' => $planRequest->user->email,
                    'avatar' => check_file($planRequest->user->avatar) ? get_file($planRequest->user->avatar) : get_file('avatars/avatar.png'),
                ],
                'plan' => [
                    'id' => $planRequest->plan->id,
                    'name' => $planRequest->plan->name,
                    'duration' => $planRequest->plan->duration,
                ],
                'status' => $planRequest->status,
                'created_at' => $planRequest->created_at,
                'approved_at' => $planRequest->approved_at,
                'rejected_at' => $planRequest->rejected_at,
            ];
        });

        return Inertia::render('plans/plan-request', [
            'planRequests' => $planRequests,
            'filters' => $request->all(['search', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page'])
        ]);
    }

    public function approve(PlanRequest $planRequest)
    {
        $planRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        // Assign the plan to the user
        $planRequest->user->update([
            'plan_id' => $planRequest->plan_id
        ]);

        // Create plan order for history
        // \App\Models\PlanOrder::create([
        //     'user_id' => $planRequest->user_id,
        //     'plan_id' => $planRequest->plan_id,
        //     'original_price' => 0,
        //     'final_price' => 0,
        //     'status' => 'approved',
        //     'ordered_at' => now()
        // ]);

        return redirect()->route('plan-requests.index')->with('success', __('Plan request approved successfully!'));
    }

    public function reject(PlanRequest $planRequest)
    {
        $planRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
        ]);

        return redirect()->route('plan-requests.index')->with('success', __('Plan request rejected successfully!'));
    }
}
