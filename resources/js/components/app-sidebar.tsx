import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useLayout } from '@/contexts/LayoutContext';
import { useSidebarSettings } from '@/contexts/SidebarContext';
import { useBrand } from '@/contexts/BrandContext';
import { type NavItem } from '@/types';
import { Link, usePage, router } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, ShoppingBag, Users, Tag, FileIcon, Settings, BarChart, Barcode, FileText, Briefcase, CheckSquare, Calendar, CreditCard, Ticket, Gift, DollarSign, MessageSquare, CalendarDays, Palette, Image, Mail, Mail as VCard, ChevronDown, Building2, Globe, Clock, Timer, Coins, Fingerprint, House } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import AppLogo from './app-logo';
import { useEffect, useState, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { hasPermission } from '@/utils/authorization';
import { toast } from '@/components/custom-toast';
import { getImagePath } from '@/utils/helpers';
import { getCompanyId } from '@/utils/helpers';


export function AppSidebar() {
    const { t, i18n } = useTranslation();
    const { auth, globalSettings, companySlug } = usePage().props as any;
    const userRole = auth.user?.type || auth.user?.role;
    const permissions = auth?.permissions || [];
    const authRoles = Array.isArray(auth?.roles) ? auth.roles : [];
    const userCan = (permission: string): boolean =>
        hasPermission(permissions, permission, userRole, authRoles);
    const isSaas = globalSettings?.is_saas;
    const hasRoute = (name: string) => {
        try {
            return route().has(name as any);
        } catch {
            return false;
        }
    };

    // Get current direction
    const isRtl = document.documentElement.dir === 'rtl';

    // Business switch handler removed

    const getSuperAdminNavItems = (): NavItem[] => [
        {
            title: t('Dashboard'),
            href: route('dashboard'),
            icon: LayoutGrid,
        },

        {
            title: t('Companies'),
            href: route('companies.index'),
            icon: Briefcase,
        },
        {
            title: t('Media Library'),
            href: route('media-library'),
            icon: Image,
        },


        {
            title: t('Plans'),
            icon: CreditCard,
            children: [
                {
                    title: t('Plan'),
                    href: route('plans.index')
                },
                {
                    title: t('Plan Request'),
                    href: route('plan-requests.index')
                },
                {
                    title: t('Plan Orders'),
                    href: route('plan-orders.index')
                }
            ]
        },
        {
            title: t('Coupons'),
            href: route('coupons.index'),
            icon: Settings,
        },

        {
            title: t('Currency'),
            href: route('currencies.index'),
            icon: DollarSign,
        },
        {
            title: t('Referral Program'),
            href: route('referral.index'),
            icon: Gift,
        },
        {
            title: t('Landing Page'),
            icon: Palette,
            children: [
                {
                    title: t('Landing Page'),
                    href: route('landing-page')
                },
                {
                    title: t('Custom Pages'),
                    href: route('landing-page.custom-pages.index')
                },
                ...(userCan( 'manage-contacts') ? [{
                    title: t('Contact Inquiries'),
                    href: route('contacts.index')
                }] : []),
                ...(userCan( 'manage-newsletters') ? [{
                    title: t('Newsletter'),
                    href: route('newsletters.index')
                }] : [])
            ]
        },
        // {
        //     title: t('Email Templates'),
        //     href: route('email-templates.index'),
        //     icon: Mail,
        // },
        {
            title: t('Settings'),
            href: route('settings'),
            icon: Settings,
        }
    ];

    const getCompanyNavItems = (): NavItem[] => {
        const items: NavItem[] = [];
        // Dashboard — permission or standard user types (company / HR / manager / employee)
        const canSeeDashboard =
            userCan( 'manage-dashboard') ||
            userCan( 'view-dashboard') ||
            ['company', 'employee', 'manager', 'hr', 'admin'].includes(auth.user?.type);

        if (canSeeDashboard) {
            items.push({
                title: t('Dashboard'),
                href: route('dashboard'),
                icon: LayoutGrid,
            });
        }



        // Staff section - only show if user has any staff-related permissions
        const staffChildren = [];
        if (userCan( 'manage-users')) {
            staffChildren.push({
                title: t('Users'),
                href: route('users.index')
            });
        }
        if (userCan( 'manage-roles')) {
            staffChildren.push({
                title: t('Roles'),
                href: route('roles.index')
            });
        }
        if (staffChildren.length > 0) {
            items.push({
                title: t('Staff'),
                icon: Users,
                children: staffChildren
            });
        }

        // Other menu items with permission checks


        // HR Module
        const hrChildren = [];
        if (userCan( 'manage-branches')) {
            hrChildren.push({
                title: t('Branches'),
                href: route('hr.branches.index')
            });
        }

        if (userCan( 'manage-departments')) {
            hrChildren.push({
                title: t('Departments'),
                href: route('hr.departments.index')
            });
        }



        if (userCan( 'manage-designations')) {
            hrChildren.push({
                title: t('Designations'),
                href: route('hr.designations.index')
            });
        }

        if (userCan( 'manage-document-types')) {
            hrChildren.push({
                title: t('Document Types'),
                href: route('hr.document-types.index')
            });
        }

        if (userCan( 'manage-employees')) {
            hrChildren.push({
                title: t('Employees'),
                href: route('hr.employees.index')
            });
        }

        if (userCan( 'manage-award-types')) {
            hrChildren.push({
                title: t('Award Types'),
                href: route('hr.award-types.index')
            });
        }

        if (userCan( 'manage-awards')) {
            hrChildren.push({
                title: t('Awards'),
                href: route('hr.awards.index')
            });
        }

        if (userCan( 'manage-promotions')) {
            hrChildren.push({
                title: t('Promotions'),
                href: route('hr.promotions.index')
            });
        }


        // Performance Module
        const performanceChildren = [];

        if (userCan( 'manage-performance-indicator-categories')) {
            performanceChildren.push({
                title: t('Indicator Categories'),
                href: route('hr.performance.indicator-categories.index')
            });
        }

        if (userCan( 'manage-performance-indicators')) {
            performanceChildren.push({
                title: t('Indicators'),
                href: route('hr.performance.indicators.index')
            });
        }

        if (userCan( 'manage-goal-types')) {
            performanceChildren.push({
                title: t('Goal Types'),
                href: route('hr.performance.goal-types.index')
            });
        }

        if (userCan( 'manage-employee-goals')) {
            performanceChildren.push({
                title: t('Employee Goals'),
                href: route('hr.performance.employee-goals.index')
            });
        }

        if (userCan( 'manage-review-cycles')) {
            performanceChildren.push({
                title: t('Review Cycles'),
                href: route('hr.performance.review-cycles.index')
            });
        }



        if (userCan( 'manage-employee-reviews')) {
            performanceChildren.push({
                title: t('Employee Reviews'),
                href: route('hr.performance.employee-reviews.index')
            });
        }

        if (performanceChildren.length > 0) {
            hrChildren.push({
                title: t('Performance'),
                children: performanceChildren
            });
        }

        if (userCan( 'manage-resignations')) {
            hrChildren.push({
                title: t('Resignations'),
                href: route('hr.resignations.index')
            });
        }

        if (userCan( 'manage-terminations')) {
            hrChildren.push({
                title: t('Terminations'),
                href: route('hr.terminations.index')
            });
        }

        if (userCan( 'manage-warnings')) {
            hrChildren.push({
                title: t('Warnings'),
                href: route('hr.warnings.index')
            });
        }

        if (userCan( 'manage-trips')) {
            hrChildren.push({
                title: t('Trips'),
                href: route('hr.trips.index')
            });
        }

        if (
            userCan( 'manage-own-claims') ||
            userCan( 'view-claims') ||
            userCan( 'create-claims')
        ) {
            hrChildren.push({ title: t('My Claims'), href: route('hr.claims.index') });
        }

        if (userCan( 'manage-claim-approvals')) {
            hrChildren.push({ title: t('Claim Approvals'), href: route('hr.claim-approvals.index') });
        }

        if (userCan( 'manage-claims')) {
            hrChildren.push({ title: t('Claim Workflows'), href: route('hr.claim-workflows.index') });
            hrChildren.push({ title: t('Claim Config'), href: route('hr.claim-config.edit') });
        }

        if (userCan( 'manage-complaints')) {
            hrChildren.push({
                title: t('Complaints'),
                href: route('hr.complaints.index')
            });
        }

        if (userCan( 'manage-employee-transfers')) {
            hrChildren.push({
                title: t('Transfers'),
                href: route('hr.transfers.index')
            });
        }

        if (userCan( 'manage-holidays')) {
            hrChildren.push({
                title: t('Holidays'),
                href: route('hr.holidays.index')
            });
        }

        if (userCan( 'manage-announcements')) {
            hrChildren.push({
                title: t('Announcements'),
                href: route('hr.announcements.index')
            });
        }

        // Asset Management submenu
        const assetChildren = [];

        if (userCan( 'manage-asset-types')) {
            assetChildren.push({
                title: t('Asset Types'),
                href: route('hr.asset-types.index')
            });
        }

        if (userCan( 'manage-assets')) {
            assetChildren.push({
                title: t('Assets'),
                href: route('hr.assets.index')
            });
        }

        if (userCan( 'manage-assets')) {
            assetChildren.push({
                title: t('Dashboard'),
                href: route('hr.assets.dashboard')
            });
        }

        if (userCan( 'manage-assets')) {
            assetChildren.push({
                title: t('Depreciation'),
                href: route('hr.assets.depreciation-report')
            });
        }

        if (assetChildren.length > 0) {
            hrChildren.push({
                title: t('Asset Management'),
                children: assetChildren
            });
        }

        // Training Management submenu
        const trainingChildren = [];

        if (userCan( 'manage-training-types')) {
            trainingChildren.push({
                title: t('Training Types'),
                href: route('hr.training-types.index')
            });
        }

        if (userCan( 'manage-training-programs')) {
            trainingChildren.push({
                title: t('Training Programs'),
                href: route('hr.training-programs.index')
            });
        }

        if (userCan( 'manage-training-sessions')) {
            trainingChildren.push({
                title: t('Training Sessions'),
                href: route('hr.training-sessions.index')
            });
        }

        if (userCan( 'manage-employee-trainings')) {
            trainingChildren.push({
                title: t('Employee Trainings'),
                href: route('hr.employee-trainings.index')
            });
        }


        // end

        if (trainingChildren.length > 0) {
            hrChildren.push({
                title: t('Training'),
                children: trainingChildren
            });
        }





        if (hrChildren.length > 0) {
            items.push({
                title: t('HR Management'),
                icon: Briefcase,
                children: hrChildren
            });
        }

        // Recruitment Management as separate menu
        const recruitmentChildren = [];

        if (userCan( 'manage-job-categories')) {
            recruitmentChildren.push({
                title: t('Job Categories'),
                href: route('hr.recruitment.job-categories.index')
            });
        }

        // if (userCan( 'manage-job-requisitions')) {
        //     recruitmentChildren.push({
        //         title: t('Job Requisitions'),
        //         href: route('hr.recruitment.job-requisitions.index')
        //     });
        // }

        if (userCan( 'manage-job-types')) {
            recruitmentChildren.push({
                title: t('Job Types'),
                href: route('hr.recruitment.job-types.index')
            });
        }

        if (userCan( 'manage-job-locations')) {
            recruitmentChildren.push({
                title: t('Job Locations'),
                href: route('hr.recruitment.job-locations.index')
            });
        }

        if (userCan( 'manage-custom-questions')) {
            recruitmentChildren.push({
                title: t('Custom Questions'),
                href: route('hr.recruitment.custom-questions.index')
            });
        }

        if (userCan( 'manage-job-postings')) {
            recruitmentChildren.push({
                title: t('Job Postings'),
                href: route('hr.recruitment.job-postings.index')
            });
        }

        if (userCan( 'manage-candidate-sources')) {
            recruitmentChildren.push({
                title: t('Candidate Sources'),
                href: route('hr.recruitment.candidate-sources.index')
            });
        }

        if (userCan( 'manage-candidates')) {
            recruitmentChildren.push({
                title: t('Candidates'),
                href: route('hr.recruitment.candidates.index')
            });
        }

        if (userCan( 'manage-interview-types')) {
            recruitmentChildren.push({
                title: t('Interview Types'),
                href: route('hr.recruitment.interview-types.index')
            });
        }

        if (userCan( 'manage-interview-rounds')) {
            recruitmentChildren.push({
                title: t('Interview Rounds'),
                href: route('hr.recruitment.interview-rounds.index')
            });
        }

        if (userCan( 'manage-interviews')) {
            recruitmentChildren.push({
                title: t('Interviews'),
                href: route('hr.recruitment.interviews.index')
            });
        }

        if (userCan( 'manage-interview-feedback')) {
            recruitmentChildren.push({
                title: t('Interview Feedback'),
                href: route('hr.recruitment.interview-feedback.index')
            });
        }



        if (userCan( 'manage-candidate-assessments')) {
            recruitmentChildren.push({
                title: t('Candidate Assessments'),
                href: route('hr.recruitment.candidate-assessments.index')
            });
        }

        if (userCan( 'manage-offer-templates')) {
            recruitmentChildren.push({
                title: t('Offer Templates'),
                href: route('hr.recruitment.offer-templates.index')
            });
        }

        if (userCan( 'manage-offers')) {
            recruitmentChildren.push({
                title: t('Offers'),
                href: route('hr.recruitment.offers.index')
            });
        }

        if (userCan( 'manage-onboarding-checklists')) {
            recruitmentChildren.push({
                title: t('Onboarding Checklists'),
                href: route('hr.recruitment.onboarding-checklists.index')
            });
        }

        if (userCan( 'manage-checklist-items')) {
            recruitmentChildren.push({
                title: t('Checklist Items'),
                href: route('hr.recruitment.checklist-items.index')
            });
        }

        if (userCan( 'manage-candidate-onboarding')) {
            recruitmentChildren.push({
                title: t('Candidate Onboarding'),
                href: route('hr.recruitment.candidate-onboarding.index')
            });
        }

        // Add Career menu item
        if (userCan( 'manage-career-page')) {
            if (companySlug) {
                recruitmentChildren.push({
                    title: t('Career'),
                    href: route('career.index', companySlug),
                    target: '_blank'
                });
            }
        }

        if (recruitmentChildren.length > 0) {
            items.push({
                title: t('Recruitment'),
                icon: Users,
                children: recruitmentChildren
            });
        }

        // Contract Management as separate menu
        const contractChildren = [];

        if (userCan( 'manage-contract-types')) {
            contractChildren.push({
                title: t('Contract Types'),
                href: route('hr.contracts.contract-types.index')
            });
        }

        if (userCan( 'manage-employee-contracts')) {
            contractChildren.push({
                title: t('Employee Contracts'),
                href: route('hr.contracts.employee-contracts.index')
            });
        }



        // if (userCan( 'manage-contract-renewals')) {
        //     contractChildren.push({
        //         title: t('Contract Renewals'),
        //         href: route('hr.contracts.contract-renewals.index')
        //     });
        // }

        if (userCan( 'manage-contract-templates')) {
            contractChildren.push({
                title: t('Contract Templates'),
                href: route('hr.contracts.contract-templates.index')
            });
        }

        if (contractChildren.length > 0) {
            items.push({
                title: t('Contract Management'),
                icon: FileText,
                children: contractChildren
            });
        }

        // Document Management as separate menu
        const documentChildren = [];

        if (userCan( 'manage-document-categories')) {
            documentChildren.push({
                title: t('Document Categories'),
                href: route('hr.documents.document-categories.index')
            });
        }

        if (userCan( 'manage-hr-documents')) {
            documentChildren.push({
                title: t('HR Documents'),
                href: route('hr.documents.hr-documents.index')
            });
        }



        if (userCan( 'manage-document-acknowledgments')) {
            documentChildren.push({
                title: t('Acknowledgments'),
                href: route('hr.documents.document-acknowledgments.index')
            });
        }

        if (userCan( 'manage-document-templates')) {
            documentChildren.push({
                title: t('Document Templates'),
                href: route('hr.documents.document-templates.index')
            });
        }

        if (documentChildren.length > 0) {
            items.push({
                title: t('Document Management'),
                icon: Folder,
                children: documentChildren
            });
        }



        // Meeting Management submenu
        const meetingChildren = [];

        if (userCan( 'manage-meeting-types')) {
            meetingChildren.push({
                title: t('Meeting Types'),
                href: route('meetings.meeting-types.index')
            });
        }

        if (userCan( 'manage-meeting-rooms')) {
            meetingChildren.push({
                title: t('Meeting Rooms'),
                href: route('meetings.meeting-rooms.index')
            });
        }

        if (userCan( 'manage-meetings')) {
            meetingChildren.push({
                title: t('Meetings'),
                href: route('meetings.meetings.index')
            });
        }

        if (userCan( 'manage-meeting-attendees')) {
            meetingChildren.push({
                title: t('Meeting Attendees'),
                href: route('meetings.meeting-attendees.index')
            });
        }

        if (userCan( 'manage-meeting-minutes')) {
            meetingChildren.push({
                title: t('Meeting Minutes'),
                href: route('meetings.meeting-minutes.index')
            });
        }

        if (userCan( 'manage-action-items')) {
            meetingChildren.push({
                title: t('Action Items'),
                href: route('meetings.action-items.index')
            });
        }



        if (meetingChildren.length > 0) {
            items.push({
                title: t('Meetings'),
                icon: Calendar,
                children: meetingChildren
            });
        }




        if (userCan( 'view-calendar') || userCan( 'manage-calendar')) {
            items.push({
                title: t('Calendar'),
                href: route('calendar.index'),
                icon: Calendar,
            });
        }

        if (userCan( 'manage-media')) {
            items.push({
                title: t('Media Library'),
                href: route('media-library'),
                icon: Image,
            });
        }

        if (
            userCan( 'manage-wfh-applications') ||
            userCan( 'manage-own-wfh-applications') ||
            userCan( 'view-wfh-applications') ||
            userCan( 'create-wfh-applications')
        ) {
            items.push({
                title: t('WFH Requests'),
                href: route('hr.wfh-applications.index'),
                icon: House,
            });
        }

        // Leave Management as separate menu
        const leaveChildren = [];

        if (userCan( 'manage-leave-types')) {
            leaveChildren.push({
                title: t('Leave Types'),
                href: route('hr.leave-types.index')
            });
        }

        if (userCan( 'manage-leave-policies')) {
            leaveChildren.push({
                title: t('Leave Policies'),
                href: route('hr.leave-policies.index')
            });
        }

        if (userCan( 'manage-leave-applications')) {
            leaveChildren.push({
                title: t('Leave Applications'),
                href: route('hr.leave-applications.index')
            });
        }

        if (userCan( 'manage-leave-balances')) {
            leaveChildren.push({
                title: t('Leave Balances'),
                href: route('hr.leave-balances.index')
            });
            leaveChildren.push({
                title: t('Monthly PL accrual logs'),
                href: route('hr.monthly-pl-accrual-logs.index')
            });
        }

        if (leaveChildren.length > 0) {
            items.push({
                title: t('Leave Management'),
                icon: CalendarDays,
                children: leaveChildren
            });
        }

        // Attendance Management as separate menu
        const attendanceChildren = [];

        if (userCan( 'manage-shifts')) {
            attendanceChildren.push({
                title: t('Shifts'),
                href: route('hr.shifts.index')
            });
        }

        if (userCan( 'manage-attendance-policies')) {
            attendanceChildren.push({
                title: t('Attendance Policies'),
                href: route('hr.attendance-policies.index')
            });
        }

        if (userCan( 'manage-attendance-records')) {
            attendanceChildren.push({
                title: t('Attendance Records'),
                href: route('hr.attendance-records.index')
            });
            if (auth.user?.type === 'company' || authRoles.includes('company')) {
                attendanceChildren.push({
                    title: t('Attendance Register'),
                    href: route('hr.muster-roll.index')
                });
            }
        }

        if (
            auth.user?.type === 'employee' &&
            userCan( 'manage-own-attendance-records')
        ) {
            attendanceChildren.push({
                title: t('My Attendance Register'),
                href: route('hr.my-attendance-register.index')
            });
        }

        if (userCan( 'manage-attendance-regularizations')) {
            attendanceChildren.push({
                title: t('Attendance Regularizations'),
                href: route('hr.attendance-regularizations.index')
            });

        }
        if (
            userCan( 'manage-attendance-records') &&
            (auth.user?.type === 'company' || authRoles.includes('company'))
        ) {
            attendanceChildren.push({
                title: t('Late Penalty Logs'),
                href: route('hr.late-penalty-logs.index')
            });
        }

        if (attendanceChildren.length > 0) {
            items.push({
                title: t('Attendance'),
                icon: Clock,
                children: attendanceChildren
            });
        }

        // Biometric Attendance
        if (userCan( 'manage-biometric-attendance')) {
            items.push({
                title: t('Biometric Attendance'),
                href: route('hr.biometric-attendance.index'),
                icon: Fingerprint,
            });
        }


        // Time Tracking as separate menu
        const timeTrackingChildren = [];
        const timeEntriesIndexRoute = route('hr.time-entries.index');
        const dailyTimesheetFormRoute = hasRoute('hr.daily-timesheets.form')
            ? route('hr.daily-timesheets.form')
            : timeEntriesIndexRoute;
        const myTimesheetsRoute = hasRoute('hr.daily-timesheets.my.index')
            ? route('hr.daily-timesheets.my.index')
            : timeEntriesIndexRoute;
        const approvalsRoute = hasRoute('hr.daily-timesheets.approvals.index')
            ? route('hr.daily-timesheets.approvals.index')
            : timeEntriesIndexRoute;

        if (userCan( 'manage-time-entries')) {
            timeTrackingChildren.push({
                title: t('Time Entries'),
                href: route('hr.time-entries.index')
            });
        }

        if (userCan( 'manage-own-time-entries')) {
            timeTrackingChildren.push({
                title: t('Employee Daily Timesheet'),
                href: dailyTimesheetFormRoute
            });
            timeTrackingChildren.push({
                title: t('My Timesheets'),
                href: myTimesheetsRoute
            });
        }

        if (userCan( 'approve-time-entries')) {
            timeTrackingChildren.push({
                title: t('Timesheet Approvals'),
                href: approvalsRoute
            });
        }

        if (timeTrackingChildren.length > 0) {
            items.push({
                title: t('Time Tracking'),
                icon: Timer,
                children: timeTrackingChildren
            });
        }

        if (
            userCan( 'manage-dashboard') &&
            ['company', 'admin', 'superadmin'].includes(auth.user?.type)
        ) {
            items.push({
                title: t('HR Notifications'),
                href: route('hr.notifications.index'),
                icon: MessageSquare,
            });
        }

        if (
            hasRoute('email-templates.index') &&
            ['company', 'admin', 'superadmin'].includes(auth.user?.type)
        ) {
            items.push({
                title: t('Email Templates'),
                href: route('email-templates.index'),
                icon: Mail,
            });
        }

        // Payroll Management as separate menu
        const payrollChildren = [];

        if (userCan( 'manage-salary-components')) {
            payrollChildren.push({
                title: t('Salary Components'),
                href: route('hr.salary-components.index')
            });
        }

        if (userCan( 'manage-employee-salaries')) {
            payrollChildren.push({
                title: t('Employee Salaries'),
                href: route('hr.employee-salaries.index')
            });
        }

        if (userCan( 'manage-payroll-runs')) {
            payrollChildren.push({
                title: t('Payroll Runs'),
                href: route('hr.payroll-runs.index')
            });
        }

        if (userCan( 'manage-payslips')) {
            payrollChildren.push({
                title: t('Payslips'),
                href: route('hr.payslips.index')
            });
        }



        if (payrollChildren.length > 0) {
            items.push({
                title: t('Payroll Management'),
                icon: DollarSign,
                children: payrollChildren
            });
        }

        // Plans section
        const planChildren = [];
        if (userCan( 'manage-plans')) {
            planChildren.push({
                title: t('Plans'),
                href: route('plans.index')
            });
        }

        if (userCan( 'view-plan-requests')) {
            planChildren.push({
                title: t('Plan Requests'),
                href: route('plan-requests.index')
            });
        }

        if (userCan( 'view-plan-orders')) {
            planChildren.push({
                title: t('Plan Orders'),
                href: route('plan-orders.index')
            });
        }

        if (planChildren.length > 0) {
            items.push({
                title: t('Plans'),
                icon: CreditCard,
                children: planChildren
            });
        }

        if (userCan( 'manage-referral')) {
            items.push({
                title: t('Referral Program'),
                href: route('referral.index'),
                icon: Gift,
            });
        }

        // Currencies - only show in non-SaaS mode for company users
        if (!isSaas && userCan( 'manage-currencies')) {
            items.push({
                title: t('Currency'),
                href: route('currencies.index'),
                icon: Coins,
            });
        }


        // Landing Page - only show in non-SaaS mode for company users
        if (!isSaas && userCan( 'manage-landing-page')) {
            items.push({
                title: t('Landing Page'),
                icon: Palette,
                children: [
                    {
                        title: t('Landing Page'),
                        href: route('landing-page')
                    },
                    {
                        title: t('Custom Pages'),
                        href: route('landing-page.custom-pages.index')
                    },
                    ...(userCan( 'manage-contacts') ? [{
                        title: t('Contact Inquiries'),
                        href: route('contacts.index')
                    }] : []),
                    ...(userCan( 'manage-newsletters') ? [{
                        title: t('Newsletter'),
                        href: route('newsletters.index')
                    }] : [])
                ]
            });
        }

        if (userCan( 'manage-settings')) {
            items.push({
                title: t('Settings'),
                href: route('settings'),
                icon: Settings,
            });
        }

        return items;
    };

    const mainNavItems = userRole === 'superadmin' ? getSuperAdminNavItems() : getCompanyNavItems();

    const { position, effectivePosition } = useLayout();
    const { variant, collapsible, style } = useSidebarSettings();
    const { logoLight, logoDark, favicon, updateBrandSettings } = useBrand();
    const [sidebarStyle, setSidebarStyle] = useState({});

    useEffect(() => {

        // Apply styles based on sidebar style
        if (style === 'colored') {
            setSidebarStyle({ backgroundColor: 'var(--primary)', color: 'white' });
        } else if (style === 'gradient') {
            setSidebarStyle({
                background: 'linear-gradient(to bottom, var(--primary), color-mix(in srgb, var(--primary), transparent 20%))',
                color: 'white'
            });
        } else {
            setSidebarStyle({});
        }
    }, [style]);

    const filteredNavItems = mainNavItems;

    // Get the first available menu item's href for logo link
    const getFirstAvailableHref = () => {
        if (filteredNavItems.length === 0) return route('dashboard');

        const firstItem = filteredNavItems[0];
        if (firstItem.href) {
            return firstItem.href;
        } else if (firstItem.children && firstItem.children.length > 0) {
            return firstItem.children[0].href || route('dashboard');
        }
        return route('dashboard');
    };

    return (
        <Sidebar
            side={effectivePosition}
            collapsible={collapsible}
            variant={variant}
            className={style !== 'plain' ? 'sidebar-custom-style' : ''}
        >
            <SidebarHeader className={style !== 'plain' ? 'sidebar-styled' : ''} style={sidebarStyle}>
                <div className="flex justify-center items-center p-2">
                    <Link href={getFirstAvailableHref()} prefetch className="flex items-center justify-center">
                        {/* Logo for expanded sidebar */}
                        <div className="group-data-[collapsible=icon]:hidden flex items-center">
                            {(() => {
                                const isDark = document.documentElement.classList.contains('dark');
                                const currentLogo = isDark ? logoLight : logoDark;
                                const displayUrl = getImagePath(currentLogo) ?? currentLogo;

                                return displayUrl ? (
                                    <img
                                        key={`${currentLogo}-${Date.now()}`}
                                        src={displayUrl}
                                        alt="Logo"
                                        className="w-auto transition-all duration-200"
                                        onError={() => updateBrandSettings({ [isDark ? 'logoLight' : 'logoDark']: '' })}
                                    />
                                ) : (
                                    <div className="h-12 text-inherit font-semibold flex items-center text-lg tracking-tight">
                                        Fast Trade Technologies Pvt. Ltd.
                                    </div>
                                );
                            })()}
                        </div>

                        {/* Icon for collapsed sidebar */}
                        <div className="h-8 w-8 hidden group-data-[collapsible=icon]:block">
                            {(() => {
                                const displayFavicon = favicon ? getImagePath(favicon) : '';

                                return displayFavicon ? (
                                    <img
                                        key={`${favicon}-${Date.now()}`}
                                        src={displayFavicon}
                                        alt="Icon"
                                        className="h-8 w-8 transition-all duration-200"
                                        onError={() => updateBrandSettings({ favicon: '' })}
                                    />
                                ) : (
                                    <div className="h-8 w-8 bg-primary text-white rounded flex items-center justify-center font-bold shadow-sm">
                                        W
                                    </div>
                                );
                            })()}
                        </div>
                    </Link>
                </div>

                {/* Business Switcher removed */}
            </SidebarHeader>

            <SidebarContent>
                <div style={sidebarStyle} className={`h-full ${style !== 'plain' ? 'sidebar-styled' : ''}`}>
                    <NavMain items={filteredNavItems} position={effectivePosition} />
                </div>
            </SidebarContent>

            <SidebarFooter>
                {/* <NavFooter items={footerNavItems} className="mt-auto" position={position} /> */}
                {/* Profile menu moved to header */}
            </SidebarFooter>
        </Sidebar>
    );
}
