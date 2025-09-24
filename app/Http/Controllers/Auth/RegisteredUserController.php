<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => 'required|accepted',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        // Create default subscription
        $planLimits = UserSubscription::getPlanLimits('free');
        UserSubscription::create([
            'user_id' => $user->id,
            'plan' => 'free',
            'status' => 'active',
            'resumes_limit' => $planLimits['resumes_limit'],
            'resumes_used' => 0,
            'period_starts_at' => now(),
            'period_ends_at' => now()->addYear(), // Free plan for 1 year
            'features' => $planLimits['features'],
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Log the registration
        ActivityLog::logForUser($user, 'User registered', $user, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'plan' => 'free',
            'timestamp' => now(),
        ]);

        return redirect(route('dashboard', absolute: false));
    }
}