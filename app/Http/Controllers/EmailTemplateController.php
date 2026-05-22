<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = EmailTemplate::with('emailTemplateLangs');
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('from', 'like', '%' . $request->search . '%');
            });
        }
        
        // Handle sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'asc');
        
        // Validate sort field
        $allowedSortFields = ['name', 'created_at', 'id'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        
        $query->orderBy($sortField, $sortDirection);
        
        $templates = $query->paginate($request->per_page ?? 10);
        
        return Inertia::render('email-templates/index', [
            'templates' => $templates,
            'filters' => $request->all(['search', 'sort_field', 'sort_direction', 'per_page'])
        ]);
    }

    public function show(EmailTemplate $emailTemplate)
    {
        $template = $emailTemplate->load('emailTemplateLangs');
        $languages = json_decode(file_get_contents(resource_path('lang/language.json')), true);
        
        // Template-specific variables
        $variables = [];
        
        if ($template->name === 'Appointment Created') {
            $variables = [
                '{app_name}' => 'App Name',
                '{appointment_name}' => 'Appointment Name',
                '{appointment_email}' => 'Appointment Email',
                '{appointment_phone}' => 'Appointment Phone',
                '{appointment_date}' => 'Appointment Date',
                '{appointment_time}' => 'Appointment Time'
            ];
        } elseif ($template->name === 'User Created') {
            $variables = [
                '{app_url}' => 'App URL',
                '{user_name}' => 'User Name',
                '{user_email}' => 'User Email',
                '{user_password}' => 'User Password',
                '{user_type}' => 'User Type'
            ];
        } elseif (in_array($template->name, [
            'Leave Apply',
            'Leave Approve/Reject',
            'WFH Request',
            'WFH Approve/Reject',
            'Attendance Regularization Request',
            'Attendance Regularization Approve/Reject',
        ], true)) {
            $variables = [
                '{app_name}' => 'Application Name',
                '{employee_name}' => 'Employee Name',
                '{start_date}' => 'Start Date',
                '{end_date}' => 'End Date',
                '{total_days}' => 'Total Days',
                '{reason}' => 'Request Reason',
                '{status}' => 'Current Status',
                '{manager_comments}' => 'Manager Comments',
                '{date}' => 'Attendance Date',
                '{requested_clock_in}' => 'Requested Clock In Time',
                '{requested_clock_out}' => 'Requested Clock Out Time',
            ];
        }

        return Inertia::render('email-templates/show', [
            'template' => $template,
            'languages' => $languages,
            'variables' => $variables
        ]);
    }

    public function updateSettings(EmailTemplate $emailTemplate, Request $request)
    {
        try {
            $request->validate([
                'from' => 'required|string|max:255'
            ]);

            $emailTemplate->update([
                'from' => $request->from
            ]);
            
            return redirect()->back()->with('success', __('Template settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update template settings: :error', ['error' => $e->getMessage()]));
        }
    }

    public function updateContent(EmailTemplate $emailTemplate, Request $request)
    {
        try {
            $request->validate([
                'lang' => 'required|string|max:10',
                'subject' => 'required|string|max:255',
                'content' => 'required|string'
            ]);

            $emailTemplate->emailTemplateLangs()
                ->where('lang', $request->lang)
                ->update([
                    'subject' => $request->subject,
                    'content' => $request->content
                ]);
            
            return redirect()->back()->with('success', __('Email content updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update email content: :error', ['error' => $e->getMessage()]));
        }
    }
}
