<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->type === 'employee') {
            if (! $user->hasPermissionTo('view-calendar')) {
                abort(403, 'Unauthorized');
            }
        } else {
            if (! $user->hasPermissionTo('manage-calendar') && ! $user->hasPermissionTo('view-calendar')) {
                abort(403, 'Unauthorized');
            }
        }

        $companyUserIds = getCompanyAndUsersId();

        if (isDemo()) {
            // Static data for demo mode - 12 months
            $meetings = collect();
            $holidays = collect();
            $leaves = collect();
            
            for ($month = 1; $month <= 12; $month++) {
                $date = now()->month($month);
                
                // 3 meetings per month
                $meetings->push([
                    'id' => 'meeting_' . $month . '_1',
                    'title' => 'Team Meeting',
                    'start' => $date->copy()->day(5)->format('Y-m-d').'T10:00:00',
                    'end' => $date->copy()->day(5)->format('Y-m-d').'T11:00:00',
                    'type' => 'meeting',
                    'status' => 'scheduled',
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6',
                ]);
                
                $meetings->push([
                    'id' => 'meeting_' . $month . '_2',
                    'title' => 'Project Review',
                    'start' => $date->copy()->day(12)->format('Y-m-d').'T14:00:00',
                    'end' => $date->copy()->day(12)->format('Y-m-d').'T15:30:00',
                    'type' => 'meeting',
                    'status' => 'scheduled',
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6',
                ]);
                
                $meetings->push([
                    'id' => 'meeting_' . $month . '_3',
                    'title' => 'Client Presentation',
                    'start' => $date->copy()->day(20)->format('Y-m-d').'T09:00:00',
                    'end' => $date->copy()->day(20)->format('Y-m-d').'T10:30:00',
                    'type' => 'meeting',
                    'status' => 'scheduled',
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#3b82f6',
                ]);
                
                // 3 holidays per month
                $holidays->push([
                    'id' => 'holiday_' . $month . '_1',
                    'title' => 'Company Foundation Day',
                    'start' => $date->copy()->day(1)->format('Y-m-d'),
                    'end' => $date->copy()->day(1)->format('Y-m-d'),
                    'type' => 'holiday',
                    'allDay' => true,
                    'backgroundColor' => '#10b77f',
                    'borderColor' => '#10b77f',
                ]);
                
                $holidays->push([
                    'id' => 'holiday_' . $month . '_2',
                    'title' => 'National Holiday',
                    'start' => $date->copy()->day(15)->format('Y-m-d'),
                    'end' => $date->copy()->day(15)->format('Y-m-d'),
                    'type' => 'holiday',
                    'allDay' => true,
                    'backgroundColor' => '#10b77f',
                    'borderColor' => '#10b77f',
                ]);
                
                $holidays->push([
                    'id' => 'holiday_' . $month . '_3',
                    'title' => 'Festival Holiday',
                    'start' => $date->copy()->day(25)->format('Y-m-d'),
                    'end' => $date->copy()->day(25)->format('Y-m-d'),
                    'type' => 'holiday',
                    'allDay' => true,
                    'backgroundColor' => '#10b77f',
                    'borderColor' => '#10b77f',
                ]);
                
                // 3 leaves per month
                $leaves->push([
                    'id' => 'leave_' . $month . '_1',
                    'title' => 'John Doe - Sick Leave',
                    'start' => $date->copy()->day(3)->format('Y-m-d'),
                    'end' => $date->copy()->day(5)->format('Y-m-d'),
                    'type' => 'leave',
                    'allDay' => true,
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                ]);
                
                $leaves->push([
                    'id' => 'leave_' . $month . '_2',
                    'title' => 'Jane Smith - Annual Leave',
                    'start' => $date->copy()->day(10)->format('Y-m-d'),
                    'end' => $date->copy()->day(13)->format('Y-m-d'),
                    'type' => 'leave',
                    'allDay' => true,
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                ]);
                
                $leaves->push([
                    'id' => 'leave_' . $month . '_3',
                    'title' => 'Mike Johnson - Casual Leave',
                    'start' => $date->copy()->day(22)->format('Y-m-d'),
                    'end' => $date->copy()->day(23)->format('Y-m-d'),
                    'type' => 'leave',
                    'allDay' => true,
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                ]);
            }
        } else {
            // Get meetings
            $meetings = Meeting::query()
                ->when($user->hasRole('employee'), function ($query) use ($user) {
                    $query->where('organizer_id', $user->id)
                        ->orWhereHas('attendees', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                }, function ($query) use ($companyUserIds) {
                    $query->whereIn('created_by', $companyUserIds);
                })
                ->get()
                ->map(function ($meeting) {
                    return [
                        'id' => $meeting->id,
                        'title' => $meeting->title,
                        'start' => Carbon::parse($meeting->meeting_date)->format('Y-m-d').'T'.Carbon::parse($meeting->start_time)->format('H:i:s'),
                        'end' => Carbon::parse($meeting->meeting_date)->format('Y-m-d').'T'.Carbon::parse($meeting->end_time)->format('H:i:s'),
                        'type' => 'meeting',
                        'status' => $meeting->status,
                        'backgroundColor' => '#3b82f6',
                        'borderColor' => '#3b82f6',
                    ];
                });

            // Get holidays
            $holidays = Holiday::whereIn('created_by', $companyUserIds)
                ->get()
                ->map(function ($holiday) {
                    return [
                        'id' => $holiday->id,
                        'title' => $holiday->name,
                        'start' => $holiday->start_date,
                        'end' => $holiday->end_date ?: $holiday->start_date,
                        'type' => 'holiday',
                        'allDay' => true,
                        'backgroundColor' => '#10b77f',
                        'borderColor' => '#10b77f',
                    ];
                });

            // Get leave applications
            $leaves = LeaveApplication::whereIn('created_by', $companyUserIds)
                ->where('status', 'approved')
                ->with(['employee', 'leaveType'])
                ->get()
                ->map(function ($leave) {
                    return [
                        'id' => $leave->id,
                        'title' => $leave->employee->name.' - '.$leave->leaveType->name,
                        'start' => $leave->start_date,
                        'end' => Carbon::parse($leave->end_date)->addDay()->format('Y-m-d'),
                        'type' => 'leave',
                        'allDay' => true,
                        'backgroundColor' => '#f59e0b',
                        'borderColor' => '#f59e0b',
                    ];
                });
        }

        $events = $meetings->concat($holidays)->concat($leaves);

        return Inertia::render('calendar/index', [
            'events' => $events,
            'canManage' => $user->hasPermissionTo('manage-calendar'),
        ]);
    }
}
