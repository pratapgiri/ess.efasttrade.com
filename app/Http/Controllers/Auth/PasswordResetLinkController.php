<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
            'settings' => settings(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $this->setEmailConfig($request->email);

            Password::sendResetLink(
                $request->only('email')
            );

            return back()->with('status', __('A reset link will be sent if the account exists.'));
        } catch (\Exception $e) {
            return back()->withErrors(['email' => __('Unable to send reset link. Please try again.')]);
        }
    }

    private function setEmailConfig($email): void
    {
        try {
            $user = User::where('email', $email)->first();
            if (! $user) {
                return;
            }
            // Resolve the company owner for this user when available; otherwise fallback to the same user.
            // This keeps reset email config working for employee, company, admin, and superadmin accounts.
            $settingsOwnerId = getCompanyId($user->id) ?? $user->id;
            $settingsOwner = User::find($settingsOwnerId) ?? $user;

            $getSettings = settings($settingsOwner->id);

            $settings = [
                'driver' => $getSettings['email_driver'] ?? '',
                'host' => $getSettings['email_host'] ?? '',
                'port' => $getSettings['email_port'] ?? '',
                'username' => $getSettings['email_username'] ?? '',
                'password' => $getSettings['email_password'] ?? '',
                'encryption' => $getSettings['email_encryption'] ?? '',
                'fromAddress' => $getSettings['email_from_address'] ?? '',
                'fromName' => $getSettings['email_from_name'] ?? '',
            ];

            Config::set([
                'mail.default' => $settings['driver'],
                'mail.mailers.smtp.host' => $settings['host'],
                'mail.mailers.smtp.port' => $settings['port'],
                'mail.mailers.smtp.encryption' => $settings['encryption'] === 'none' ? null : $settings['encryption'],
                'mail.mailers.smtp.username' => $settings['username'],
                'mail.mailers.smtp.password' => $settings['password'],
                'mail.from.address' => $settings['fromAddress'],
                'mail.from.name' => $settings['fromName'],
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Email config error: '.$e->getMessage());
        }
    }
}
