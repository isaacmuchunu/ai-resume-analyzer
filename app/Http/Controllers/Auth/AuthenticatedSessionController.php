<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use App\Models\UserAnalytics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();

            $request->session()->regenerate();

            $user = Auth::user();

            // Log the login activity
            ActivityLog::logForUser($user, 'User logged in', null, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
            ]);

            // Track analytics
            UserAnalytics::incrementForUser($user->id, 'page_views');

            return redirect()->intended(route('dashboard', absolute: false));

        } catch (ValidationException $e) {
            // Log failed login attempt
            ActivityLog::logActivity('Failed login attempt', null, [
                'email' => $request->input('email'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
            ]);

            throw $e;
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Log the logout activity
        if ($user) {
            ActivityLog::logForUser($user, 'User logged out', null, [
                'ip_address' => $request->ip(),
                'timestamp' => now(),
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}