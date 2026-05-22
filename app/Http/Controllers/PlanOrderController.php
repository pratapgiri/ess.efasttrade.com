<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\PlanOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PlanOrderController extends BaseController
{
    public function index(Request $request)
    {
        $query = PlanOrder::with(['user', 'plan', 'coupon', 'processedBy']);

        // For Company
        if (Auth::user()->hasRole('company')) {
            $query->where('user_id', Auth::user()->id);
        }

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
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
            $query->whereDate('ordered_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('ordered_at', '<=', $request->date_to);
        }

        // Handle sorting
        $sortField = $request->get('sort_field', 'ordered_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validate sort field
        $allowedSortFields = ['id', 'ordered_at', 'status', 'final_price'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'ordered_at';
        }
        
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 10);
        $planOrders = $query->paginate($perPage);

        // Transform data for frontend
        $planOrders->getCollection()->transform(function ($planOrder) {
            return [
                'id' => $planOrder->id,
                'order_number' => $planOrder->order_number,
                'user' => [
                    'id' => $planOrder->user->id,
                    'name' => $planOrder->user->name,
                    'email' => $planOrder->user->email,
                    'avatar' => check_file($planOrder->user->avatar) ? get_file($planOrder->user->avatar) : get_file('avatars/avatar.png'),
                ],
                'plan' => [
                    'id' => $planOrder->plan->id,
                    'name' => $planOrder->plan->name,
                ],
                'original_price' => $planOrder->original_price,
                'discount_amount' => $planOrder->discount_amount,
                'final_price' => $planOrder->final_price,
                'status' => $planOrder->status,
                'ordered_at' => $planOrder->ordered_at,
                'processed_at' => $planOrder->processed_at,
            ];
        });

        // Always use super admin currency for plan pricing
        $superAdmin = User::where('type', 'superadmin')->first();
        $superAdminSettings = settings($superAdmin->id);
        $currency = $superAdminSettings ? ($superAdminSettings['defaultCurrency'] ?? 'USD') : 'USD';
        $currencySymbol = '$';
        if (!empty($currency)) {
            $currencyData = Currency::where('code', $currency)->first();
            $currencySymbol = $currencyData ? $currencyData->symbol : '$';
        }

        return Inertia::render('plans/plan-orders', [
            'planOrders' => $planOrders,
            'filters' => $request->all(['search', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            'currency' => $currency,
            'currencySymbol' => $currencySymbol
        ]);
    }

    public function approve(PlanOrder $planOrder)
    {
        try {
            $planOrder->approve(Auth::id());

            return redirect()->back()->with('success', __('Plan order approved successfully!'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to approve plan order'));
        }
    }

    public function reject(Request $request, PlanOrder $planOrder)
    {
        try {
            $request->validate([
                'notes' => 'nullable|string|max:500'
            ]);

            $planOrder->reject(Auth::id(), $request->notes);

            return redirect()->back()->with('success', __('Plan order rejected successfully!'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to reject plan order'));
        }
    }
}
