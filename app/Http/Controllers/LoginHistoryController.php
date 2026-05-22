<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginHistoryController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-login-history')) {
            $query = LoginHistory::with('user:id,name,email,type')->where(function ($q) {
                if (isSaaS()) {
                    if (Auth::user()->hasRole('superadmin')) {
                        $q->where('created_by', Auth::id());
                    } else if (Auth::user()->hasRole('company')) {
                        $q->where('created_by', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                } else {
                    if (Auth::user()->hasRole('company')) {
                        $q->where('created_by', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                }
            });

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('ip', 'like', "%{$search}%");
            }

            // Sorting
            $sortField = $request->get('sort_field', 'date');
            $sortDirection = $request->get('sort_direction', 'desc');

            // Validate sort field
            $allowedSortFields = ['date', 'ip'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'date';
            }

            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $loginHistory = $query->paginate($perPage)->withQueryString();

            return Inertia::render('login-history/index', [
                'loginHistory' => $loginHistory,
                'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'per_page'])
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }


    }

    public function destroy(LoginHistory $loginDetail)
    {
        if (Auth::user()->can('delete-login-history')) {
            $loginDetail->delete();
            return redirect()->back()->with('success', 'Login history deleted successfully.');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}