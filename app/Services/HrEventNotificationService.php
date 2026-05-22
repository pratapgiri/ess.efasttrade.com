<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateLang;
use App\Models\HrNotification;
use App\Models\User;
use App\Models\UserEmailTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class HrEventNotificationService
{
    private const TEMPLATE_MAP = [
        'leave_apply' => 'Leave Apply',
        'leave_status' => 'Leave Approve/Reject',
        'wfh_apply' => 'WFH Request',
        'wfh_status' => 'WFH Approve/Reject',
        'ar_apply' => 'Attendance Regularization Request',
        'ar_status' => 'Attendance Regularization Approve/Reject',
        'claim_apply' => 'Claim Submitted',
        'claim_forward' => 'Claim Forwarded',
        'claim_reject' => 'Claim Rejected',
        'claim_approve' => 'Claim Approved',
    ];

    public function notifyEvent(string $eventKey, int $companyId, array $variables, Collection $recipients, array $meta = []): void
    {
        $companyId = $this->resolveCompanyId($companyId);
        $variables = $this->normalizeDateVariables($variables, $companyId);

        if (! isset(self::TEMPLATE_MAP[$eventKey])) {
            return;
        }

        $templateName = self::TEMPLATE_MAP[$eventKey];
        $template = $this->ensureTemplate($templateName, $eventKey, $companyId);
        $this->configureSmtpForCompany($companyId);

        $templateLang = $template->emailTemplateLangs()->where('lang', 'en')->first();
        if (! $templateLang) {
            return;
        }

        $subject = $this->replaceVariables($templateLang->subject, $variables);
        $content = $this->replaceVariables($templateLang->content, $variables);
        $fromName = $this->replaceVariables((string) ($template->from ?? config('app.name')), $variables);

        foreach ($recipients as $recipient) {
            if (! $recipient || empty($recipient->email)) {
                continue;
            }

            $status = 'sent';
            $errorMessage = null;

            try {
                Mail::send([], [], function ($message) use ($recipient, $subject, $content, $fromName) {
                    $message->to($recipient->email, $recipient->name ?? null)
                        ->subject($subject)
                        ->html($content)
                        ->from(config('mail.from.address'), $fromName ?: config('mail.from.name'));
                });
            } catch (\Throwable $e) {
                $status = 'failed';
                $errorMessage = $e->getMessage();
            }

            HrNotification::create([
                'company_id' => $companyId,
                'event_key' => $eventKey,
                'title' => $subject,
                'message' => strip_tags($content),
                'recipient_user_id' => $recipient->id,
                'recipient_email' => $recipient->email,
                'status' => $status,
                'error_message' => $errorMessage,
                'meta' => $meta,
                'created_by' => Auth::id(),
            ]);
        }
    }

    public function getApproversByPermission(int $companyId, string $permission): Collection
    {
        $companyId = $this->resolveCompanyId($companyId);

        return User::permission($permission)
            ->where('status', 'active')
            ->where(function ($query) use ($companyId) {
                $query->where('id', $companyId)->orWhere('created_by', $companyId);
            })
            ->get(['id', 'name', 'email']);
    }

    private function resolveCompanyId(int $companyId): int
    {
        $resolvedCompanyId = getCompanyId($companyId);

        return (int) ($resolvedCompanyId ?: $companyId);
    }

    private function ensureTemplate(string $templateName, string $eventKey, int $companyId): EmailTemplate
    {
        $template = EmailTemplate::firstOrCreate(
            ['name' => $templateName],
            [
                'from' => config('app.name', 'HRMS'),
                'user_id' => $companyId,
            ]
        );

        $defaults = $this->defaultTemplateContent($eventKey);
        EmailTemplateLang::firstOrCreate(
            [
                'parent_id' => $template->id,
                'lang' => 'en',
            ],
            [
                'subject' => $defaults['subject'],
                'content' => $defaults['content'],
            ]
        );

        UserEmailTemplate::firstOrCreate(
            [
                'template_id' => $template->id,
                'user_id' => $companyId,
            ],
            [
                'is_active' => true,
            ]
        );

        return $template;
    }

    private function defaultTemplateContent(string $eventKey): array
    {
        $templates = [
            'leave_apply' => [
                'subject' => 'Leave request submitted by {employee_name}',
                'content' => '<p>Hello,</p><p><strong>{employee_name}</strong> has submitted a leave request from <strong>{start_date}</strong> to <strong>{end_date}</strong> for <strong>{total_days}</strong> day(s).</p><p>Reason: {reason}</p><p>Status: {status}</p>',
            ],
            'leave_status' => [
                'subject' => 'Your leave request was {status}',
                'content' => '<p>Hello {employee_name},</p><p>Your leave request from <strong>{start_date}</strong> to <strong>{end_date}</strong> has been <strong>{status}</strong>.</p><p>Manager comments: {manager_comments}</p>',
            ],
            'wfh_apply' => [
                'subject' => 'WFH request submitted by {employee_name}',
                'content' => '<p>Hello,</p><p><strong>{employee_name}</strong> has submitted a WFH request from <strong>{start_date}</strong> to <strong>{end_date}</strong>.</p><p>Reason: {reason}</p><p>Status: {status}</p>',
            ],
            'wfh_status' => [
                'subject' => 'Your WFH request was {status}',
                'content' => '<p>Hello {employee_name},</p><p>Your WFH request from <strong>{start_date}</strong> to <strong>{end_date}</strong> has been <strong>{status}</strong>.</p><p>Manager comments: {manager_comments}</p>',
            ],
            'ar_apply' => [
                'subject' => 'Attendance regularization request by {employee_name}',
                'content' => '<p>Hello,</p><p><strong>{employee_name}</strong> has submitted attendance regularization for <strong>{date}</strong>.</p><p>Requested in/out: {requested_clock_in} - {requested_clock_out}</p><p>Reason: {reason}</p><p>Status: {status}</p>',
            ],
            'ar_status' => [
                'subject' => 'Your attendance regularization request was {status}',
                'content' => '<p>Hello {employee_name},</p><p>Your attendance regularization request for <strong>{date}</strong> has been <strong>{status}</strong>.</p><p>Manager comments: {manager_comments}</p>',
            ],
            'claim_apply' => [
                'subject' => 'Claim {claim_no} submitted by {employee_name}',
                'content' => '<p>A new claim <strong>{claim_no}</strong> ({claim_type}) for amount <strong>{amount}</strong> was submitted by {employee_name}.</p><p>Status: {status}</p>',
            ],
            'claim_forward' => [
                'subject' => 'Claim {claim_no} requires your action',
                'content' => '<p>Claim <strong>{claim_no}</strong> from {employee_name} is pending at <strong>{pending_at}</strong>.</p><p>Amount: {amount}</p>',
            ],
            'claim_reject' => [
                'subject' => 'Claim {claim_no} was rejected',
                'content' => '<p>Claim <strong>{claim_no}</strong> for {employee_name} has been rejected.</p><p>Status: {status}</p>',
            ],
            'claim_approve' => [
                'subject' => 'Claim {claim_no} approved',
                'content' => '<p>Claim <strong>{claim_no}</strong> for {employee_name} has been approved.</p><p>Amount: {amount}</p>',
            ],
        ];

        return $templates[$eventKey] ?? $templates['ar_status'];
    }

    private function replaceVariables(string $content, array $variables): string
    {
        return str_replace(array_keys($variables), array_values($variables), $content);
    }

    private function normalizeDateVariables(array $variables, int $companyId): array
    {
        $settings = settings($companyId);
        $dateFormat = $settings['dateFormat'] ?? 'Y-m-d';
        $dateKeys = ['{date}', '{start_date}', '{end_date}'];

        foreach ($dateKeys as $dateKey) {
            if (! isset($variables[$dateKey]) || $variables[$dateKey] === '' || $variables[$dateKey] === '-') {
                continue;
            }

            try {
                $variables[$dateKey] = Carbon::parse((string) $variables[$dateKey])->format($dateFormat);
            } catch (\Throwable $e) {
                // Keep original value when it cannot be parsed.
            }
        }

        return $variables;
    }

    private function configureSmtpForCompany(int $companyId): void
    {
        $settings = settings($companyId);
        Config::set([
            'mail.default' => $settings['email_driver'] ?? 'smtp',
            'mail.mailers.smtp.host' => $settings['email_host'] ?? config('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => $settings['email_port'] ?? config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.encryption' => ($settings['email_encryption'] ?? 'tls') === 'none' ? null : ($settings['email_encryption'] ?? 'tls'),
            'mail.mailers.smtp.username' => $settings['email_username'] ?? config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password' => $settings['email_password'] ?? config('mail.mailers.smtp.password'),
            'mail.from.address' => $settings['email_from_address'] ?? config('mail.from.address'),
            'mail.from.name' => $settings['email_from_name'] ?? config('mail.from.name'),
        ]);
    }
}
