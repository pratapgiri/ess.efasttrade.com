<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $templates = [
            'Leave Apply' => [
                'subject' => 'Leave request submitted by {employee_name}',
                'content' => '<p>Hello,</p><p><strong>{employee_name}</strong> has submitted a leave request from <strong>{start_date}</strong> to <strong>{end_date}</strong> for <strong>{total_days}</strong> day(s).</p><p>Reason: {reason}</p><p>Status: {status}</p>',
            ],
            'Leave Approve/Reject' => [
                'subject' => 'Your leave request was {status}',
                'content' => '<p>Hello {employee_name},</p><p>Your leave request from <strong>{start_date}</strong> to <strong>{end_date}</strong> has been <strong>{status}</strong>.</p><p>Manager comments: {manager_comments}</p>',
            ],
            'WFH Request' => [
                'subject' => 'WFH request submitted by {employee_name}',
                'content' => '<p>Hello,</p><p><strong>{employee_name}</strong> has submitted a WFH request from <strong>{start_date}</strong> to <strong>{end_date}</strong>.</p><p>Reason: {reason}</p><p>Status: {status}</p>',
            ],
            'WFH Approve/Reject' => [
                'subject' => 'Your WFH request was {status}',
                'content' => '<p>Hello {employee_name},</p><p>Your WFH request from <strong>{start_date}</strong> to <strong>{end_date}</strong> has been <strong>{status}</strong>.</p><p>Manager comments: {manager_comments}</p>',
            ],
            'Attendance Regularization Request' => [
                'subject' => 'Attendance regularization request by {employee_name}',
                'content' => '<p>Hello,</p><p><strong>{employee_name}</strong> has submitted attendance regularization for <strong>{date}</strong>.</p><p>Requested in/out: {requested_clock_in} - {requested_clock_out}</p><p>Reason: {reason}</p><p>Status: {status}</p>',
            ],
            'Attendance Regularization Approve/Reject' => [
                'subject' => 'Your attendance regularization request was {status}',
                'content' => '<p>Hello {employee_name},</p><p>Your attendance regularization request for <strong>{date}</strong> has been <strong>{status}</strong>.</p><p>Manager comments: {manager_comments}</p>',
            ],
        ];

        foreach ($templates as $name => $translation) {
            $templateId = DB::table('email_templates')->where('name', $name)->value('id');
            if (! $templateId) {
                $templateId = DB::table('email_templates')->insertGetId([
                    'name' => $name,
                    'from' => config('app.name', 'HRMS'),
                    'user_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $langId = DB::table('email_template_langs')
                ->where('parent_id', $templateId)
                ->where('lang', 'en')
                ->value('id');

            if (! $langId) {
                DB::table('email_template_langs')->insert([
                    'parent_id' => $templateId,
                    'lang' => 'en',
                    'subject' => $translation['subject'],
                    'content' => $translation['content'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $names = [
            'Leave Apply',
            'Leave Approve/Reject',
            'WFH Request',
            'WFH Approve/Reject',
            'Attendance Regularization Request',
            'Attendance Regularization Approve/Reject',
        ];

        $templateIds = DB::table('email_templates')->whereIn('name', $names)->pluck('id');
        if ($templateIds->isNotEmpty()) {
            DB::table('email_template_langs')->whereIn('parent_id', $templateIds)->delete();
            DB::table('user_email_templates')->whereIn('template_id', $templateIds)->delete();
            DB::table('email_templates')->whereIn('id', $templateIds)->delete();
        }
    }
};
