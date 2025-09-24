<?php

namespace App\Http\Middleware;

use App\Models\UserAnalytics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track for authenticated users
        if (auth()->check()) {
            $user = auth()->user();

            // Track page views
            UserAnalytics::incrementForUser($user->id, 'page_views');

            // Track session start time if not already set
            if (!$request->session()->has('session_start')) {
                $request->session()->put('session_start', now());
            }
        }

        return $response;
    }

    public function terminate(Request $request): void
    {
        // Update session duration on request termination
        if (auth()->check() && $request->session()->has('session_start')) {
            $sessionStart = $request->session()->get('session_start');
            $sessionDuration = now()->diffInSeconds($sessionStart);

            // Only update if session was longer than 5 seconds to avoid noise
            if ($sessionDuration > 5) {
                $analytics = UserAnalytics::firstOrCreate([
                    'user_id' => auth()->id(),
                    'date' => today(),
                ]);

                // Update session duration (average of current and new)
                $currentDuration = $analytics->session_duration ?? 0;
                $newDuration = $currentDuration > 0
                    ? ($currentDuration + $sessionDuration) / 2
                    : $sessionDuration;

                $analytics->update(['session_duration' => $newDuration]);
            }
        }
    }
}