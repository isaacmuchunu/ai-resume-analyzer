<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Admin/Auth/Login');
    }

    public function login(LoginRequest $request)
    {
        $request->authenticate();

        $user = Auth::user();

        // Check if user has admin privileges
        if (!$user->isAdmin()) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Access denied. Admin privileges required.',
            ]);
        }

        $request->session()->regenerate();

        // Log admin login
        ActivityLog::logForUser($user, 'Admin logged in', null, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            ActivityLog::logForUser($user, 'Admin logged out', null, [
                'ip_address' => $request->ip(),
                'timestamp' => now(),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}