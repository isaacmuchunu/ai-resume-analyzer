<?php

namespace App\Http\Controllers;

use App\Models\UserSubscription;
use App\Models\ActivityLog;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Exception;

class SubscriptionController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}
    public function index(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription;
        $plans = $this->paymentService->getAvailablePlans();

        $usageStats = null;
        if ($subscription) {
            $usageStats = $this->paymentService->getUsageStats($user);
        }

        return Inertia::render('Subscription/Index', [
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'stripe_customer_id' => $subscription->stripe_customer_id,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'trial_ends_at' => $subscription->trial_ends_at,
                'cancelled_at' => $subscription->cancelled_at,
                'resumes_limit' => $subscription->resumes_limit ?? -1,
                'resumes_used' => $subscription->resumes_used ?? 0,
                'remaining_resumes' => $subscription->remaining_resumes ?? -1,
                'usage_percentage' => $subscription->usage_percentage ?? 0,
                'features' => $subscription->features ?? [],
                'is_active' => $subscription->isActive(),
                'is_expired' => $subscription->isExpired(),
            ] : null,
            'available_plans' => $plans,
            'usage_stats' => $usageStats,
        ]);
    }

    public function upgrade()
    {
        return Inertia::render('Subscription/Upgrade', [
            'available_plans' => $this->paymentService->getAvailablePlans(),
            'current_plan' => auth()->user()->getCurrentPlan(),
        ]);
    }

    /**
     * Create checkout session
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'plan' => 'required|string|in:starter,professional,enterprise',
        ]);

        $user = Auth::user();
        $result = $this->paymentService->createCheckoutSession(
            $user,
            $request->plan,
            $request->only(['upgrade'])
        );

        if ($result['success']) {
            return response()->json([
                'checkout_url' => $result['checkout_url']
            ]);
        }

        return response()->json([
            'error' => $result['error']
        ], 422);
    }

    /**
     * Handle successful checkout
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        $user = Auth::user();

        // Manually sync subscription status since we don't use webhooks
        if ($sessionId) {
            try {
                // Retrieve the checkout session from Stripe
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                
                if ($session && $session->subscription) {
                    // Get the subscription from Stripe
                    $stripeSubscription = \Stripe\Subscription::retrieve($session->subscription);
                    
                    // Update or create local subscription record
                    UserSubscription::updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'stripe_customer_id' => $session->customer,
                            'stripe_subscription_id' => $stripeSubscription->id,
                            'plan' => $session->metadata->plan_id ?? 'professional',
                            'status' => 'active',
                            'current_period_start' => now()->createFromTimestamp($stripeSubscription->current_period_start),
                            'current_period_end' => now()->createFromTimestamp($stripeSubscription->current_period_end),
                            'trial_ends_at' => $stripeSubscription->trial_end ?
                                now()->createFromTimestamp($stripeSubscription->trial_end) : null,
                        ]
                    );
                    
                    // Log successful subscription activation
                    ActivityLog::logForUser($user, 'Subscription activated via checkout', null, [
                        'session_id' => $sessionId,
                        'plan' => $session->metadata->plan_id ?? 'professional',
                        'timestamp' => now(),
                    ]);
                }
            } catch (Exception $e) {
                // Log error but don't fail the success page
                \Illuminate\Support\Facades\Log::error('Failed to sync subscription after checkout', [
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return Inertia::render('Subscription/Success', [
            'sessionId' => $sessionId,
            'message' => 'Your subscription has been activated successfully!'
        ]);
    }

    /**
     * Handle cancelled checkout
     */
    public function checkoutCancel()
    {
        return Inertia::render('Subscription/Cancel', [
            'message' => 'Subscription checkout was cancelled.'
        ]);
    }

    /**
     * Create customer portal session
     */
    public function portal()
    {
        $user = Auth::user();
        $result = $this->paymentService->createPortalSession($user);

        if ($result['success']) {
            return response()->json([
                'portal_url' => $result['portal_url']
            ]);
        }

        return response()->json([
            'error' => $result['error']
        ], 422);
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

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request)
    {
        $request->validate([
            'immediately' => 'boolean'
        ]);

        $user = Auth::user();
        $result = $this->paymentService->cancelSubscription(
            $user,
            $request->boolean('immediately', false)
        );

        if ($result['success']) {
            return response()->json([
                'message' => $result['message'],
                'ends_at' => $result['ends_at']
            ]);
        }

        return response()->json([
            'error' => $result['error']
        ], 422);
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
        $request->validate([
            'period' => 'string|in:current_month,last_month,current_billing'
        ]);

        $user = $request->user();
        $period = $request->get('period', 'current_month');

        $stats = $this->paymentService->getUsageStats($user, $period);

        return response()->json($stats);
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