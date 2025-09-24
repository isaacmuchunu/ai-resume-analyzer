<?php

namespace App\Http\Controllers;

use App\Models\UserSubscription;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription;

        return Inertia::render('Subscription/Index', [
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'resumes_limit' => $subscription->resumes_limit,
                'resumes_used' => $subscription->resumes_used,
                'remaining_resumes' => $subscription->remaining_resumes,
                'usage_percentage' => $subscription->usage_percentage,
                'period_starts_at' => $subscription->period_starts_at,
                'period_ends_at' => $subscription->period_ends_at,
                'days_remaining' => $subscription->days_remaining,
                'features' => $subscription->features,
                'is_active' => $subscription->isActive(),
                'is_expired' => $subscription->isExpired(),
            ] : null,
            'available_plans' => $this->getAvailablePlans(),
        ]);
    }

    public function upgrade()
    {
        return Inertia::render('Subscription/Upgrade', [
            'available_plans' => $this->getAvailablePlans(),
            'current_plan' => auth()->user()->getCurrentPlan(),
        ]);
    }

    public function changePlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:free,basic,pro,enterprise',
        ]);

        $user = $request->user();
        $newPlan = $request->plan;
        $planLimits = UserSubscription::getPlanLimits($newPlan);

        // Get or create subscription
        $subscription = $user->subscription ?? new UserSubscription(['user_id' => $user->id]);

        $oldPlan = $subscription->plan ?? 'none';

        // Update subscription
        $subscription->fill([
            'plan' => $newPlan,
            'status' => 'active',
            'resumes_limit' => $planLimits['resumes_limit'],
            'features' => $planLimits['features'],
            'period_starts_at' => now(),
            'period_ends_at' => now()->addMonth(),
        ]);

        $subscription->save();

        // Log the plan change
        ActivityLog::logForUser($user, 'Subscription plan changed', $subscription, [
            'old_plan' => $oldPlan,
            'new_plan' => $newPlan,
            'timestamp' => now(),
        ]);

        return redirect()->route('subscription.index')
            ->with('success', "Successfully upgraded to {$newPlan} plan!");
    }

    public function cancel(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        $subscription->cancel();

        // Log cancellation
        ActivityLog::logForUser($user, 'Subscription cancelled', $subscription, [
            'plan' => $subscription->plan,
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Subscription cancelled successfully.');
    }

    public function usage(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return response()->json(['error' => 'No active subscription'], 404);
        }

        return response()->json([
            'usage' => [
                'resumes_used' => $subscription->resumes_used,
                'resumes_limit' => $subscription->resumes_limit,
                'remaining_resumes' => $subscription->remaining_resumes,
                'usage_percentage' => $subscription->usage_percentage,
                'can_upload' => $subscription->canUploadResume(),
            ]
        ]);
    }

    private function getAvailablePlans(): array
    {
        return [
            'free' => [
                'name' => 'Free',
                'price' => 0,
                'period' => 'forever',
                'resumes_limit' => 5,
                'features' => [
                    'Basic resume analysis',
                    'ATS compatibility check',
                    'Email support',
                    'Download analyzed resumes',
                ],
                'limitations' => [
                    'Limited to 5 resumes',
                    'Basic analysis only',
                ],
            ],
            'basic' => [
                'name' => 'Basic',
                'price' => 9.99,
                'period' => 'month',
                'resumes_limit' => 25,
                'features' => [
                    'Everything in Free',
                    'Advanced analysis algorithms',
                    'Keyword optimization',
                    'Skills gap analysis',
                    'Priority email support',
                ],
                'popular' => false,
            ],
            'pro' => [
                'name' => 'Professional',
                'price' => 29.99,
                'period' => 'month',
                'resumes_limit' => 100,
                'features' => [
                    'Everything in Basic',
                    'Comprehensive AI insights',
                    'Industry-specific analysis',
                    'Custom templates',
                    'Advanced analytics dashboard',
                    'API access',
                    'Priority support',
                ],
                'popular' => true,
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => 99.99,
                'period' => 'month',
                'resumes_limit' => -1, // unlimited
                'features' => [
                    'Everything in Professional',
                    'Unlimited resume analysis',
                    'White-label solution',
                    'SSO integration',
                    'Dedicated support',
                    'Custom integrations',
                    'Advanced security features',
                ],
                'contact_sales' => true,
            ],
        ];
    }
}