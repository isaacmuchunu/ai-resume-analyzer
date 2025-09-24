<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct(private Request $request) {}

    /**
     * Handle user login events.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;
        $ipAddress = $this->request->ip();

        // Record successful login
        $user->recordLogin($ipAddress);

        // Log successful login
        Log::info('User login successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ipAddress,
            'user_agent' => $this->request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        // Log activity
        $user->logActivity('User logged in', null, [
            'ip_address' => $ipAddress,
            'user_agent' => $this->request->userAgent(),
        ]);
    }

    /**
     * Handle failed login events.
     */
    public function handleFailedLogin(Failed $event): void
    {
        $user = $event->user;
        $ipAddress = $this->request->ip();

        if ($user) {
            // Increment login attempts
            $user->incrementLoginAttempts();

            // Log failed login attempt
            Log::warning('User login failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ipAddress,
                'user_agent' => $this->request->userAgent(),
                'login_attempts' => $user->login_attempts,
                'is_locked' => $user->isLocked(),
                'timestamp' => now()->toISOString(),
            ]);

            // Log activity
            $user->logActivity('Failed login attempt', null, [
                'ip_address' => $ipAddress,
                'user_agent' => $this->request->userAgent(),
                'login_attempts' => $user->login_attempts,
            ]);
        } else {
            // Log failed login with unknown email
            Log::warning('Login attempt with unknown email', [
                'email' => $this->request->input('email'),
                'ip_address' => $ipAddress,
                'user_agent' => $this->request->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Handle lockout events.
     */
    public function handleLockout(Lockout $event): void
    {
        $ipAddress = $this->request->ip();

        Log::warning('User account locked due to too many attempts', [
            'email' => $this->request->input('email'),
            'ip_address' => $ipAddress,
            'user_agent' => $this->request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        // You might want to implement additional security measures here
        // such as notifying the user via email about the lockout
    }
}