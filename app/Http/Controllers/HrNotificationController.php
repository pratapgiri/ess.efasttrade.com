<?php

namespace App\Http\Controllers;

use App\Models\HrNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HrNotificationController extends Controller
{
    private function resolveAuthorizedCompanyId(): ?int
    {
        $authUser = Auth::user();
        if (! in_array($authUser->type, ['company', 'admin', 'superadmin'], true)) {
            return null;
        }

        return (int) (getCompanyId($authUser->id) ?? $authUser->id);
    }

    public function index(Request $request)
    {
        $companyId = $this->resolveAuthorizedCompanyId();
        if (! $companyId) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $query = HrNotification::with(['recipient:id,name,email', 'creator:id,name'])
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('message', 'like', '%'.$request->search.'%')
                    ->orWhere('recipient_email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('event_key') && $request->event_key !== 'all') {
            $query->where('event_key', $request->event_key);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $notifications = $query->orderByDesc('id')->paginate($request->per_page ?? 10);

        return Inertia::render('hr/notifications/index', [
            'notifications' => $notifications,
            'filters' => $request->all(['search', 'event_key', 'status', 'per_page']),
            'eventOptions' => [
                'leave_apply',
                'leave_status',
                'wfh_apply',
                'wfh_status',
                'ar_apply',
                'ar_status',
            ],
        ]);
    }

    public function destroy(HrNotification $notification)
    {
        $companyId = $this->resolveAuthorizedCompanyId();
        if (! $companyId) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        if ((int) $notification->company_id !== $companyId) {
            return redirect()->back()->with('error', __('Notification not found.'));
        }

        $notification->delete();

        return redirect()->back()->with('success', __('Notification deleted successfully.'));
    }

    public function bulkDestroy(Request $request)
    {
        $companyId = $this->resolveAuthorizedCompanyId();
        if (! $companyId) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        HrNotification::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return redirect()->back()->with('success', __('Selected notifications deleted successfully.'));
    }
}
