<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next, string $feature = null): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $subscription = $user->activeSubscription;

        // If no active subscription, redirect to upgrade page
        if (!$subscription) {
            return redirect()->route('subscription.upgrade')
                ->with('error', 'You need an active subscription to access this feature.');
        }

        // Check specific feature if provided
        if ($feature && !$subscription->hasFeature($feature)) {
            return redirect()->route('subscription.upgrade')
                ->with('error', "Your current plan doesn't include access to {$feature}.");
        }

        return $next($request);
    }
}