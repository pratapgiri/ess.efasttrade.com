<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        $demoBusinesses = [];

        if (config('app.is_demo')) {
            // Get the company user
            $companyUser = \App\Models\User::where('email', 'company@example.com')->first();
        }

        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'settings' => settings(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
            $request->session()->regenerate();

            // Check if email verification is enabled and user is not verified
            $emailVerificationEnabled = getSetting('emailVerification', false);
            if ($emailVerificationEnabled && !$request->user()->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            $user = Auth::user();

            // Safely get IP address
            $ip = $request->ip() ?? '127.0.0.1';
            try {
                // Get location data with timeout and error handling
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 5,
                        'ignore_errors' => true
                    ]
                ]);
                $response = @file_get_contents('http://ip-api.com/php/' . $ip, false, $context);
                $query = $response ? @unserialize($response) : [];
                if (!is_array($query)) {
                    $query = [];
                }
            } catch (\Exception $e) {
                $query = [];
            }

            try {
                // Browser detection with error handling
                $userAgent = $request->header('User-Agent', '');
                if (!empty($userAgent)) {
                    $whichbrowser = new \WhichBrowser\Parser($userAgent);
                    // Skip if it's a bot
                    if (isset($whichbrowser->device->type) && $whichbrowser->device->type == 'bot') {
                        return redirect()->intended(route('dashboard', absolute: false));
                    }

                    $query['browser_name'] = $whichbrowser->browser->name ?? null;
                    $query['os_name'] = $whichbrowser->os->name ?? null;
                }
            } catch (\Exception $e) {
                // Continue without browser detection if it fails
            }

            // Get referrer safely
            $referrer = $request->header('Referer') ? parse_url($request->header('Referer')) : null;

            // Set additional details
            $query['browser_language'] = $request->header('Accept-Language') ? mb_substr($request->header('Accept-Language'), 0, 2) : null;
            $query['device_type'] = class_exists('Utility') ? getDeviceType($userAgent) : 'unknown';
            $query['referrer_host'] = !empty($referrer['host']) ? $referrer['host'] : null;
            $query['referrer_path'] = !empty($referrer['path']) ? $referrer['path'] : null;

            // Set timezone safely
            if (isset($query['timezone']) && !empty($query['timezone'])) {
                try {
                    date_default_timezone_set($query['timezone']);
                } catch (\Exception $e) {
                    // Continue with default timezone if setting fails
                }
            }

            // Save login details
            try {

                /* if (isSaaS()) {
                    if (Auth::user()->hasRole('superadmin')) {
                        $createdBy = Auth::user()->id;
                    } else if (Auth::user()->hasRole('company')) {
                        $createdBy = Auth::user()->created_by;
                    } else {
                        $createdBy = getCompanyId(Auth::user()->id);
                    }
                } else {
                    if (Auth::user()->hasRole('company')) {
                        $createdBy = Auth::user()->id;
                    } else {
                        $createdBy = getCompanyId(Auth::user()->id);
                    }
                }*/

                $authUser = Auth::user();
                $createdBy = null;
                if (isSaaS()) {
                    if ($authUser->hasRole('superadmin')) {
                        $createdBy = $authUser->id;
                    } elseif ($authUser->hasRole('company')) {
                        $createdBy = $authUser->created_by ?: $authUser->id;
                    } else {
                        $createdBy = getCompanyId($authUser->id);
                    }
                } else {
                    if ($authUser->hasRole('company')) {
                        $createdBy = $authUser->id;
                    } else {
                        $createdBy = getCompanyId($authUser->id);
                    }
                }

                // Final fallback to avoid NULL for NOT NULL DB column
                $createdBy = $createdBy ?: ($user->id ?? Auth::id() ?? 0);


                $loginDetail = new LoginHistory();
                $loginDetail->user_id = $user->id;
                $loginDetail->ip = $ip;
                $loginDetail->date = now();
                $loginDetail->Details = json_encode($query);
                $loginDetail->created_by = $createdBy;
                $loginDetail->save();

            } catch (\Exception $e) {
                Log::warning('Failed to save login details: ' . $e->getMessage());
            }

            return redirect()->intended(route('dashboard', absolute: false));

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return back()->withErrors(['email' => $e->getMessage()]);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
