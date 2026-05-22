<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Http\Requests\CouponRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CouponController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Coupon::with('creator');

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Handle type filter
        if ($request->has('type') && !empty($request->type) && $request->type !== 'all') {
            $query->where('type', $request->type);
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
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validate sort field
        $allowedSortFields = ['id', 'name', 'code', 'type', 'expiry_date', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        
        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 10);
        $coupons = $query->paginate($perPage);

        return Inertia::render('coupons/index', [
            'coupons' => $coupons,
            'filters' => $request->all(['search', 'type', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page'])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Coupon $coupon)
    {
        $coupon->load('creator');
        
        // Get usage history (mock data for now - you'll need to implement actual usage tracking)
        $usageHistory = collect([
            // Mock usage data - replace with actual usage model query
            [
                'id' => 1,
                'user_name' => 'John Doe',
                'user_email' => 'john@example.com',
                'order_id' => 'ORD-001',
                'amount' => 100.00,
                'discount_amount' => 10.00,
                'used_at' => now()->subDays(2)->toISOString()
            ],
            [
                'id' => 2,
                'user_name' => 'Jane Smith',
                'user_email' => 'jane@example.com',
                'order_id' => 'ORD-002',
                'amount' => 150.00,
                'discount_amount' => 15.00,
                'used_at' => now()->subDays(1)->toISOString()
            ]
        ]);
        
        // Paginate the usage history
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $total = $usageHistory->count();
        $items = $usageHistory->forPage($page, $perPage)->values();
        
        $paginatedUsage = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page']
        );
        
        // Add used_count to coupon (mock for now)
        $coupon->used_count = $usageHistory->count();
        
        return Inertia::render('coupons/show', [
            'coupon' => $coupon,
            'usage_history' => $paginatedUsage
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CouponRequest $request)
    {

        $data = $request->all();
        $data['created_by'] = Auth::id();

        // Generate code if auto-generate is selected
        if ($request->code_type === 'auto') {
            do {
                $data['code'] = strtoupper(Str::random(8));
            } while (Coupon::where('code', $data['code'])->exists());
        }

        $coupon = Coupon::create($data);

        return redirect()->route('coupons.index')->with('success', __('Coupon created successfully!'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CouponRequest $request, Coupon $coupon)
    {

        $data = $request->all();

        // Generate new code if switching to auto-generate
        if ($request->code_type === 'auto' && $coupon->code_type !== 'auto') {
            do {
                $data['code'] = strtoupper(Str::random(8));
            } while (Coupon::where('code', $data['code'])->where('id', '!=', $coupon->id)->exists());
        }

        $coupon->update($data);

        return redirect()->route('coupons.index')->with('success', __('Coupon updated successfully!'));
    }

    /**
     * Validate coupon code
     */
    public function validate(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'plan_id' => 'required|integer',
            'amount' => 'required|numeric|min:0'
        ]);
        
        $coupon = Coupon::where('code', $request->coupon_code)
            ->where('status', 1)
            ->first();
            
        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'message' => __('Invalid or inactive coupon code')
            ], 400);
        }
        
        // Check if coupon is expired
        if ($coupon->expiry_date && $coupon->expiry_date < now()) {
            return response()->json([
                'valid' => false,
                'message' => __('Coupon has expired')
            ], 400);
        }
        
        // Check usage limit
        if ($coupon->use_limit_per_coupon && $coupon->used_count >= $coupon->use_limit_per_coupon) {
            return response()->json([
                'valid' => false,
                'message' => __('Coupon usage limit exceeded')
            ], 400);
        }
        
        // Check minimum amount
        if ($coupon->minimum_spend && $request->amount < $coupon->minimum_spend) {
            return response()->json([
                'valid' => false,
                'message' => __('Minimum spend requirement not met')
            ], 400);
        }
        
        return response()->json([
            'valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => $coupon->discount_amount
            ]
        ]);
    }

    /**
     * Toggle the status of the specified coupon.
     */
    public function toggleStatus(Coupon $coupon)
    {
        $coupon->update([
            'status' => !$coupon->status
        ]);

        return redirect()->back()->with('success', __('Coupon status updated successfully!'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('coupons.index')->with('success', __('Coupon deleted successfully!'));
    }
}
