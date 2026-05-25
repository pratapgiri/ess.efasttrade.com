<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobPosting;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Meeting;
use App\Models\Plan;
use App\Models\PlanOrder;
use App\Models\PlanRequest;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

       // print_r($user); exit();

        // Super admin always gets dashboard
        if ($user->type === 'superadmin' || $user->type === 'super admin') {
            return $this->renderDashboard();
        }

        // Company owner account always gets dashboard
        if ($user->type === 'company' || $user->hasRole('company')) {
            return $this->renderDashboard();
        }

        // Users with dashboard permission (employee, HR, manager, etc.)
        try {
            if ($user->hasPermissionTo('manage-dashboard') || $user->hasPermissionTo('view-dashboard')) {
                return $this->renderDashboard();
            }
        } catch (\Exception $e) {
            // Permission tables missing during install — allow dashboard
            return $this->renderDashboard();
        }

        // No dashboard permission: send to first module they can access
        return $this->redirectToFirstAvailablePage();
    }

    public function redirectToFirstAvailablePage()
    {
        $user = auth()->user();
        //print_r($user); exit();

        // Fallback routes when user has no dashboard permission
        $routes = [
            ['route' => 'hr.claims.index', 'permission' => 'manage-own-claims'],
            ['route' => 'hr.claims.index', 'permission' => 'view-claims'],
            ['route' => 'hr.claims.index', 'permission' => 'create-claims'],
            ['route' => 'hr.leave-applications.index', 'permission' => 'manage-leave-applications'],
            ['route' => 'hr.leave-applications.index', 'permission' => 'view-leave-applications'],
            ['route' => 'hr.attendance-records.index', 'permission' => 'manage-attendance-records'],
            ['route' => 'hr.attendance-records.index', 'permission' => 'view-attendance-records'],
            ['route' => 'users.index', 'permission' => 'manage-users'],
            ['route' => 'roles.index', 'permission' => 'manage-roles'],
            ['route' => 'plans.index', 'permission' => 'manage-plans'],
            ['route' => 'referral.index', 'permission' => 'manage-referral'],
            ['route' => 'settings.index', 'permission' => 'manage-settings'],
        ];

        foreach ($routes as $routeData) {
            try {
                if ($user->hasPermissionTo($routeData['permission'])) {
                    return redirect()->route($routeData['route']);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        auth()->logout();

        return redirect()->route('login')->with('error', __('No access permissions found.'));
    }

    private function renderDashboard()
    {
        $user = auth()->user();
        //print_r($user); exit();

        if ($user->type === 'superadmin' || $user->type === 'super admin') {
            return $this->renderSuperAdminDashboard();
        } else {
            return $this->renderCompanyDashboard();
        }
    }

    private function renderSuperAdminDashboard()
    {
        // Get system-wide statistics
        $totalCompanies = User::where('type', 'company')->count();
        $totalUsers = User::where('type', '!=', 'superadmin')->where('type', '!=', 'super admin')->count();
        $totalRevenue = PlanOrder::where('status', 'approved')->sum('final_price') ?? 0;
        $activePlans = Plan::where('is_plan_enable', 'on')->count();

        $pendingRequests = PlanRequest::where('status', 'pending')->count();

        // Calculate monthly growth for companies
        $currentMonthCompanies = User::where('type', 'company')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $previousMonthCompanies = User::where('type', 'company')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $monthlyGrowth = $previousMonthCompanies > 0
            ? round((($currentMonthCompanies - $previousMonthCompanies) / $previousMonthCompanies) * 100, 1)
            : ($currentMonthCompanies > 0 ? 100 : 0);

        $dashboardData = [
            'stats' => [
                'totalCompanies' => $totalCompanies,
                'totalUsers' => $totalUsers,
                'totalRevenue' => $totalRevenue,
                'activePlans' => $activePlans,
                'pendingRequests' => $pendingRequests,
                'monthlyGrowth' => $monthlyGrowth,
            ],
            'recentActivity' => User::where('type', 'company')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'email' => $company->email,
                        'registered_at' => $company->created_at->diffForHumans(),
                        'status' => 'active',
                    ];
                }),
            'topPlans' => Plan::withCount('users')
                ->orderBy('users_count', 'desc')
                ->take(5)
                ->get()
                ->map(function ($plan) {
                    return [
                        'name' => $plan->name,
                        'subscribers' => $plan->users_count,
                        'revenue' => $plan->users_count * $plan->price,
                    ];
                }),
        ];

        return Inertia::render('superadmin/dashboard', props: [
            'dashboardData' => $dashboardData,
        ]);
    }

    private function renderCompanyDashboard()
    {
        $user = auth()->user();

        // If user is employee, show limited dashboard
        if ($user->type === 'employee') {
            return $this->renderEmployeeDashboard();
        }

        $companyUserIds = $this->getCompanyUserIds();

        // Core HR Statistics
        $totalEmployees = User::where('type', 'employee')->whereIn('created_by', $companyUserIds)->count();
        $totalBranches = Branch::whereIn('created_by', $companyUserIds)->count();
        $totalDepartments = Department::whereIn('created_by', $companyUserIds)->count();

        // Monthly Statistics
        if (isDemo()) {
            $newEmployeesThisMonth = Employee::whereIn('created_by', $companyUserIds)->count();
            $jobPostsThisMonth = JobPosting::whereIn('created_by', $companyUserIds)->count();
            $candidatesThisMonth = Candidate::whereIn('created_by', $companyUserIds)->count();
        } else {
            $newEmployeesThisMonth = Employee::whereIn('created_by', $companyUserIds)
                ->whereMonth('created_at', now()->month)->count();
            $jobPostsThisMonth = JobPosting::whereIn('created_by', $companyUserIds)
                ->whereMonth('created_at', now()->month)->count();
            $candidatesThisMonth = Candidate::whereIn('created_by', $companyUserIds)
                ->whereMonth('created_at', now()->month)->count();
        }

        // Attendance Statistics
        if (isDemo()) {
            $presentToday = 45;
            $attendanceRate = 85.5;
        } else {
            $presentToday = AttendanceRecord::whereIn('created_by', $companyUserIds)
                ->whereDate('date', today())->where('status', 'present')->count();
            $attendanceRate = $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100, 1) : 0;
        }

        // Leave Statistics
        $pendingLeaves = LeaveApplication::whereIn('created_by', $companyUserIds)
            ->where('status', 'pending')->count();

        $onLeaveToday = LeaveApplication::whereIn('created_by', $companyUserIds)
            ->where('status', 'approved');

        $onLeaveToday = $onLeaveToday->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())->count();

        // Recruitment Statistics
        $activeJobPostings = JobPosting::whereIn('created_by', $companyUserIds)
            ->where('status', 'Published')->count();
        $totalCandidates = Candidate::whereIn('created_by', $companyUserIds)->count();

        // Department Distribution for Chart
        $predefinedColors = ['#4F46E5', '#10b77f', '#F59E0B', '#EF4444', '#3B82F6', '#D946EF'];

        $departmentStats = Department::whereIn('created_by', $companyUserIds)
            ->withCount('employees')
            ->with('branch')
            ->orderBy('employees_count', 'desc')
            ->when(config('app.is_demo') == true, function ($query) {
                return $query->take(6);
            })
            ->get()
            ->map(function ($dept, $index) use ($predefinedColors) {
                $displayName = $dept->name.' ('.$dept->branch->name.')';

                return [
                    'name' => $displayName,
                    'value' => $dept->employees_count,
                    'color' => config('app.is_demo') == true
                        ? ($predefinedColors[$index] ?? '#'.substr(md5($displayName), 0, 6))
                        : '#'.substr(md5($displayName), 0, 6),
                ];
            });

        // Monthly Hiring Trend for Chart (last 6 months)
        if (isDemo()) {
            $hiringTrend = [
                ['month' => now()->subMonths(5)->format('M Y'), 'hires' => 8],
                ['month' => now()->subMonths(4)->format('M Y'), 'hires' => 12],
                ['month' => now()->subMonths(3)->format('M Y'), 'hires' => 15],
                ['month' => now()->subMonths(2)->format('M Y'), 'hires' => 10],
                ['month' => now()->subMonths(1)->format('M Y'), 'hires' => 18],
                ['month' => now()->format('M Y'), 'hires' => 14],
            ];
        } else {
            $hiringTrend = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $count = Employee::whereIn('created_by', $companyUserIds)
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->count();
                $hiringTrend[] = [
                    'month' => $month->format('M Y'),
                    'hires' => $count,
                ];
            }
        }

        // Candidate Status Distribution for Chart
        $candidateStatusStats = Candidate::whereIn('created_by', $companyUserIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                $colors = [
                    'New' => '#3B82F6',
                    'Screening' => '#06B6D4',
                    'Interview' => '#6366F1',
                    'Offer' => '#F59E0B',
                    'Hired' => '#10b77f',
                    'Rejected' => '#EF4444',
                ];

                return [
                    'name' => $item->status,
                    'value' => $item->count,
                    'color' => $colors[$item->status] ?? '#6b7280',
                ];
            });

        // Leave Types for Chart
        $leaveTypesStats = LeaveType::whereIn('created_by', $companyUserIds)
            ->get()
            ->map(function ($leaveType) {
                return [
                    'name' => $leaveType->name,
                    'value' => $leaveType->max_days_per_year,
                    'color' => $leaveType->color ?: '#'.substr(md5($leaveType->name), 0, 6),
                ];
            });

        // Employee Growth Chart (Monthly for current year)
        if (isDemo()) {
            $employeeGrowthChart = [
                ['month' => 'January', 'employees' => 15],
                ['month' => 'February', 'employees' => 5],
                ['month' => 'March', 'employees' => 22],
                ['month' => 'April', 'employees' => 10],
                ['month' => 'May', 'employees' => 28],
                ['month' => 'June', 'employees' => 32],
                ['month' => 'July', 'employees' => 35],
                ['month' => 'August', 'employees' => 50],
                ['month' => 'September', 'employees' => 42],
                ['month' => 'October', 'employees' => 45],
                ['month' => 'November', 'employees' => 48],
                ['month' => 'December', 'employees' => 52],
            ];
        } else {
            $employeeGrowthChart = [];
            for ($month = 1; $month <= 12; $month++) {
                $count = User::where('type', 'employee')
                    ->whereIn('created_by', $companyUserIds)
                    ->whereMonth('created_at', $month)
                    ->whereYear('created_at', now()->year)
                    ->count();
                $employeeGrowthChart[] = [
                    'month' => date('F', mktime(0, 0, 0, $month, 1)),
                    'employees' => $count,
                ];
            }
        }

        // Recent Activities
        $recentLeaves = LeaveApplication::whereIn('created_by', $companyUserIds)
            ->with(['employee', 'leaveType']);
        if (config('app.is_demo') == true) {
            $recentLeaves = $recentLeaves->whereIn('status', ['approved', 'absent'])->get();
        } else {
            $recentLeaves = $recentLeaves->whereIn('status', ['approved', 'absent'])
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->get();
        }

        $recentCandidates = Candidate::whereIn('created_by', $companyUserIds)
            ->with(['job'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Recent Announcements
        $recentAnnouncements = Announcement::whereIn('created_by', $companyUserIds)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Recent Meetings
        $recentMeetings = Meeting::whereIn('created_by', $companyUserIds)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $dashboardData = [
            'stats' => [
                'totalEmployees' => $totalEmployees,
                'totalBranches' => $totalBranches,
                'totalDepartments' => $totalDepartments,
                'newEmployeesThisMonth' => $newEmployeesThisMonth,
                'jobPostsThisMonth' => $jobPostsThisMonth,
                'candidatesThisMonth' => $candidatesThisMonth,
                'attendanceRate' => $attendanceRate,
                'presentToday' => $presentToday,
                'pendingLeaves' => $pendingLeaves,
                'onLeaveToday' => $onLeaveToday,
                'activeJobPostings' => $activeJobPostings,
                'totalCandidates' => $totalCandidates,
            ],
            'charts' => [
                'departmentStats' => $departmentStats,
                'hiringTrend' => $hiringTrend,
                'candidateStatusStats' => $candidateStatusStats,
                'leaveTypesStats' => $leaveTypesStats,
                'employeeGrowthChart' => $employeeGrowthChart,
            ],
            'recentActivities' => [
                'leaves' => $recentLeaves,
                'candidates' => $recentCandidates,
                'announcements' => $recentAnnouncements,
                'meetings' => $recentMeetings,
            ],
            'userType' => $user->type,
        ];

        return Inertia::render('dashboard', [
            'dashboardData' => $dashboardData,
        ]);
    }

    private function renderEmployeeDashboard()
    {
        $user = auth()->user();
        $companyUserIds = $this->getCompanyUserIds();

        // Recent Announcements
        $recentAnnouncements = \App\Models\Announcement::whereIn('created_by', $companyUserIds)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Recent Meetings - get meetings where user is organizer
        $recentMeetings = \App\Models\Meeting::with('attendees')
            ->whereIn('created_by', $companyUserIds)
            ->where('organizer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get meetings where user is attendee
        $meetingAttendee = \App\Models\MeetingAttendee::with('meeting')
            ->where('user_id', $user->id)
            ->get();

        // Extract meetings from attendee records
        $attendeeMeetings = $meetingAttendee->pluck(value: 'meeting')->filter();

        // Merge and remove duplicates
        $recentMeetings = $recentMeetings->merge($attendeeMeetings)
            ->unique('id')
            ->filter(function ($meeting) {
                return $meeting->meeting_date >= today();
            })
            ->sortByDesc('created_at')
            ->values();

        // Employee Stats
        $totalAwards = \App\Models\Award::where('employee_id', $user->id)->count();
        $totalWarnings = \App\Models\Warning::where('employee_id', $user->id)->count();
        $totalComplaints = \App\Models\Complaint::where('against_employee_id', $user->id)->count();

        // Get shifts and attendance policies for clock in functionality
        $shifts = \App\Models\Shift::whereIn('created_by', $companyUserIds)
            ->where('status', 'active')
            ->get(['id', 'name', 'start_time', 'end_time']);

        $attendancePolicies = \App\Models\AttendancePolicy::whereIn('created_by', $companyUserIds)
            ->where('status', 'active')
            ->get(['id', 'name']);

        // Get today's attendance for the employee
        $todayAttendance = \App\Models\AttendanceRecord::where('employee_id', $user->id)
            ->where('date', \Carbon\Carbon::today())
            ->first();

        // Get employee's assigned shift
        $employeeShift = null;
        $employee = \App\Models\Employee::where('user_id', $user->id)->first();
        if ($employee && $employee->shift_id) {
            $employeeShift = \App\Models\Shift::find($employee->shift_id);
        }

        $dashboardData = [
            'stats' => [
                'totalAwards' => $totalAwards,
                'totalWarnings' => $totalWarnings,
                'totalComplaints' => $totalComplaints,
            ],
            'recentActivities' => [
                'announcements' => $recentAnnouncements,
                'meetings' => $recentMeetings,
            ],
            'shifts' => $shifts,
            'attendancePolicies' => $attendancePolicies,
            'todayAttendance' => $todayAttendance,
            'currentTime' => \Carbon\Carbon::now()->format('H:i:s'),
            'employeeShift' => $employeeShift,
            'userType' => $user->type,
        ];

        return Inertia::render('employee-dashboard', [
            'dashboardData' => $dashboardData,
        ]);
    }

    // private function getCompanyUserIds()
    // {
    //     $user = auth()->user();
    //     if ($user->type === 'company') {
    //         $companyUserIds = User::where('created_by', $user->id)->pluck('id')->toArray();
    //         $companyUserIds[] = $user->id;
    //         return $companyUserIds;
    //     } else {
    //         $userCreatedBy = User::where('id', $user->created_by)->value('id');
    //         $companyUserIds = User::where('created_by', $userCreatedBy)->pluck('id')->toArray();
    //         $companyUserIds[] = $userCreatedBy;
    //         return $companyUserIds;
    //     }
    // }

    private function getCompanyUserIds()
    {
        $user = auth()->user();
        if ($user->type === 'company') {
            $companyId = getCompanyId($user->id);
            if ($companyId) {
                $allUsers = getAllCompanyUsers($companyId);
                $allUsers[] = $companyId; // Include company itself

                return array_unique($allUsers);
            }

            return [];
        } else {
            $companyId = getCompanyId($user->id);
            if ($companyId) {
                $allUsers = getAllCompanyUsers($companyId);
                $allUsers[] = $companyId; // Include company itself

                return array_unique($allUsers);
            }

            return [];
        }
    }
}
