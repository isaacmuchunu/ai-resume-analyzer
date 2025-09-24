<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSubscription;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentService
{
    private string $stripeSecretKey;
    private string $webhookSecret;

    public function __construct(private NotificationService $notificationService)
    {
        $this->stripeSecretKey = config('services.stripe.secret');
        $this->webhookSecret = config('services.stripe.webhook_secret');

        if ($this->stripeSecretKey) {
            \Stripe\Stripe::setApiKey($this->stripeSecretKey);
        }
    }

    /**
     * Create subscription checkout session
     */
    public function createCheckoutSession(User $user, string $planId, array $options = []): array
    {
        try {
            if (!$this->stripeSecretKey) {
                throw new Exception('Stripe not configured');
            }

            $plan = $this->getPlanDetails($planId);
            if (!$plan) {
                throw new Exception('Invalid plan selected');
            }

            // Create or get Stripe customer
            $customer = $this->createOrGetCustomer($user);

            // Create checkout session
            $session = \Stripe\Checkout\Session::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $plan['stripe_price_id'],
                    'quantity' => 1,
                ]],
                'success_url' => url('/subscription/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/subscription/cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                    'upgrade' => $options['upgrade'] ?? 'false',
                ],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan_id' => $planId,
                    ],
                    'trial_period_days' => $plan['trial_days'] ?? 0,
                ],
                'allow_promotion_codes' => true,
                'billing_address_collection' => 'auto',
            ]);

            return [
                'success' => true,
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ];

        } catch (Exception $e) {
            Log::error('Checkout session creation failed', [
                'user_id' => $user->id,
                'plan_id' => $planId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create customer portal session
     */
    public function createPortalSession(User $user): array
    {
        try {
            $subscription = $user->activeSubscription;
            if (!$subscription || !$subscription->stripe_customer_id) {
                throw new Exception('No active subscription found');
            }

            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $subscription->stripe_customer_id,
                'return_url' => url('/subscription'),
            ]);

            return [
                'success' => true,
                'portal_url' => $session->url,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(User $user, bool $immediately = false): array
    {
        try {
            $subscription = $user->activeSubscription;
            if (!$subscription || !$subscription->stripe_subscription_id) {
                throw new Exception('No active subscription found');
            }

            $stripeSubscription = \Stripe\Subscription::retrieve($subscription->stripe_subscription_id);

            if ($immediately) {
                // Cancel immediately
                $stripeSubscription->cancel();
                $subscription->update([
                    'status' => 'cancelled',
                    'period_ends_at' => now(),
                    'cancelled_at' => now(),
                ]);
            } else {
                // Cancel at period end
                $stripeSubscription->cancel_at_period_end = true;
                $stripeSubscription->save();

                $subscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
            }

            // Send cancellation notification
            $this->notificationService->sendToUser(
                $user,
                'subscription_cancelled',
                'Subscription Cancelled',
                $immediately ? 'Your subscription has been cancelled immediately.' : 'Your subscription will cancel at the end of the current billing period.',
                ['subscription_id' => $subscription->id]
            );

            return [
                'success' => true,
                'message' => $immediately ? 'Subscription cancelled immediately' : 'Subscription will cancel at period end',
                'ends_at' => $subscription->period_ends_at,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Stripe webhooks
     */
    public function handleWebhook(array $payload, string $signature): array
    {
        try {
            // Verify webhook signature
            $event = \Stripe\Webhook::constructEvent(
                json_encode($payload),
                $signature,
                $this->webhookSecret
            );

            Log::info('Stripe webhook received', [
                'type' => $event->type,
                'id' => $event->id,
            ]);

            // Handle different event types
            switch ($event->type) {
                case 'checkout.session.completed':
                    return $this->handleCheckoutCompleted($event->data->object);

                case 'invoice.payment_succeeded':
                    return $this->handlePaymentSucceeded($event->data->object);

                case 'invoice.payment_failed':
                    return $this->handlePaymentFailed($event->data->object);

                case 'customer.subscription.updated':
                    return $this->handleSubscriptionUpdated($event->data->object);

                case 'customer.subscription.deleted':
                    return $this->handleSubscriptionDeleted($event->data->object);

                default:
                    Log::info('Unhandled webhook event', ['type' => $event->type]);
                    return ['success' => true, 'message' => 'Event not handled'];
            }

        } catch (Exception $e) {
            Log::error('Webhook handling failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available plans
     */
    public function getAvailablePlans(): array
    {
        return [
            'starter' => [
                'name' => 'Starter',
                'description' => 'Perfect for job seekers just getting started',
                'price' => 9.99,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'stripe_price_id' => config('stripe.prices.starter_monthly'),
                'features' => [
                    'Up to 5 resume analyses per month',
                    'Basic AI recommendations',
                    'PDF and Word exports',
                    'Email support',
                ],
                'limits' => [
                    'monthly_analyses' => 5,
                    'exports_per_month' => 20,
                    'collaboration_shares' => 3,
                ],
                'trial_days' => 7,
            ],
            'professional' => [
                'name' => 'Professional',
                'description' => 'For active job seekers and career changers',
                'price' => 19.99,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'stripe_price_id' => config('stripe.prices.professional_monthly'),
                'features' => [
                    'Unlimited resume analyses',
                    'Advanced AI insights',
                    'All export formats',
                    'Collaboration features',
                    'Priority support',
                    'Job matching',
                ],
                'limits' => [
                    'monthly_analyses' => -1, // unlimited
                    'exports_per_month' => -1,
                    'collaboration_shares' => 25,
                ],
                'trial_days' => 14,
                'popular' => true,
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'description' => 'For teams and organizations',
                'price' => 49.99,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'stripe_price_id' => config('stripe.prices.enterprise_monthly'),
                'features' => [
                    'Everything in Professional',
                    'Team management',
                    'API access',
                    'Custom branding',
                    'Advanced analytics',
                    'Dedicated support',
                ],
                'limits' => [
                    'monthly_analyses' => -1,
                    'exports_per_month' => -1,
                    'collaboration_shares' => -1,
                    'api_calls_per_month' => 10000,
                ],
                'trial_days' => 30,
            ],
        ];
    }

    /**
     * Get usage statistics for billing
     */
    public function getUsageStats(User $user, string $period = 'current_month'): array
    {
        $subscription = $user->activeSubscription;

        $startDate = match($period) {
            'current_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            'current_billing' => $subscription ?
                $subscription->current_period_start : now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $endDate = match($period) {
            'current_month' => now()->endOfMonth(),
            'last_month' => now()->subMonth()->endOfMonth(),
            'current_billing' => $subscription ?
                $subscription->current_period_end : now()->endOfMonth(),
            default => now()->endOfMonth(),
        };

        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'usage' => [
                'analyses_performed' => $user->resumes()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->whereNotNull('analysis_status')
                    ->count(),
                'exports_generated' => $this->getExportCount($user, $startDate, $endDate),
                'api_calls_made' => $this->getApiCallCount($user, $startDate, $endDate),
                'collaboration_shares' => $user->resumeShares()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count(),
            ],
            'limits' => $this->getPlanLimits($subscription?->plan ?? 'free'),
            'subscription' => $subscription ? [
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
            ] : null,
        ];
    }

    // Private helper methods

    private function createOrGetCustomer(User $user): \Stripe\Customer
    {
        $subscription = $user->subscription;

        if ($subscription && $subscription->stripe_customer_id) {
            try {
                return \Stripe\Customer::retrieve($subscription->stripe_customer_id);
            } catch (Exception $e) {
                // Customer doesn't exist, create new one
            }
        }

        // Create new customer
        $customer = \Stripe\Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        return $customer;
    }

    private function getPlanDetails(string $planId): ?array
    {
        $plans = $this->getAvailablePlans();
        return $plans[$planId] ?? null;
    }

    private function handleCheckoutCompleted($session): array
    {
        $userId = $session->metadata->user_id ?? null;
        if (!$userId) {
            throw new Exception('User ID not found in session metadata');
        }

        $user = User::find($userId);
        if (!$user) {
            throw new Exception('User not found');
        }

        // Retrieve the subscription from Stripe
        $stripeSubscription = \Stripe\Subscription::retrieve($session->subscription);

        // Create or update subscription record
        UserSubscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'stripe_customer_id' => $session->customer,
                'stripe_subscription_id' => $stripeSubscription->id,
                'plan' => $session->metadata->plan_id,
                'status' => 'active',
                'current_period_start' => now()->createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_end' => now()->createFromTimestamp($stripeSubscription->current_period_end),
                'trial_ends_at' => $stripeSubscription->trial_end ?
                    now()->createFromTimestamp($stripeSubscription->trial_end) : null,
            ]
        );

        // Send welcome email
        $this->notificationService->sendToUser(
            $user,
            'subscription_activated',
            'Welcome to ' . ($session->metadata->plan_id ?? 'Premium'),
            'Your subscription has been activated successfully!',
            ['plan' => $session->metadata->plan_id]
        );

        return ['success' => true, 'message' => 'Subscription activated'];
    }

    private function handlePaymentSucceeded($invoice): array
    {
        // Handle successful recurring payment
        $customerId = $invoice->customer;
        $subscription = UserSubscription::where('stripe_customer_id', $customerId)->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'active',
                'last_payment_date' => now(),
            ]);

            $this->notificationService->sendToUser(
                $subscription->user,
                'payment_succeeded',
                'Payment Successful',
                'Your subscription payment was processed successfully.',
                ['amount' => $invoice->amount_paid / 100]
            );
        }

        return ['success' => true];
    }

    private function handlePaymentFailed($invoice): array
    {
        $customerId = $invoice->customer;
        $subscription = UserSubscription::where('stripe_customer_id', $customerId)->first();

        if ($subscription) {
            $subscription->update(['status' => 'past_due']);

            $this->notificationService->sendToUser(
                $subscription->user,
                'payment_failed',
                'Payment Failed',
                'We were unable to process your subscription payment. Please update your payment method.',
                ['amount' => $invoice->amount_due / 100]
            );
        }

        return ['success' => true];
    }

    private function handleSubscriptionUpdated($subscription): array
    {
        $userSubscription = UserSubscription::where('stripe_subscription_id', $subscription->id)->first();

        if ($userSubscription) {
            $userSubscription->update([
                'status' => $subscription->status,
                'current_period_start' => now()->createFromTimestamp($subscription->current_period_start),
                'current_period_end' => now()->createFromTimestamp($subscription->current_period_end),
            ]);
        }

        return ['success' => true];
    }

    private function handleSubscriptionDeleted($subscription): array
    {
        $userSubscription = UserSubscription::where('stripe_subscription_id', $subscription->id)->first();

        if ($userSubscription) {
            $userSubscription->update([
                'status' => 'cancelled',
                'period_ends_at' => now(),
                'cancelled_at' => now(),
            ]);

            $this->notificationService->sendToUser(
                $userSubscription->user,
                'subscription_cancelled',
                'Subscription Cancelled',
                'Your subscription has been cancelled.',
                ['ended_at' => now()->toISOString()]
            );
        }

        return ['success' => true];
    }

    private function getExportCount(User $user, $startDate, $endDate): int
    {
        // This would track exports in a separate table
        return 0; // Placeholder
    }

    private function getApiCallCount(User $user, $startDate, $endDate): int
    {
        // This would track API calls in a separate table
        return 0; // Placeholder
    }

    private function getPlanLimits(string $plan): array
    {
        $plans = $this->getAvailablePlans();
        return $plans[$plan]['limits'] ?? [
            'monthly_analyses' => 3,
            'exports_per_month' => 5,
            'collaboration_shares' => 0,
        ];
    }
}