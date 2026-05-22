<?php

namespace App\Http\Controllers;

use App\Models\NewsLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-newsletters')) {
            $query = NewsLetter::query();

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            // Validate sort field
            $allowedSortFields = ['email', 'status', 'created_at'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }
            
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $newsletters = $query->paginate($perPage)->withQueryString();

            return Inertia::render('newsletters/index', [
                'newsletters' => $newsletters,
                'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'per_page'])
            ]);

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function destroy(NewsLetter $newsletter)
    {
        if (Auth::user()->can('delete-newsletters')) {
            $newsletter->delete();

            return redirect()->back()->with('success', 'Newsletter subscription deleted successfully.');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}