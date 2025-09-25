import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import {
    ArrowLeft,
    Crown,
    Check,
    X,
    CreditCard,
    Calendar,
    Users,
    Zap,
    Shield,
    AlertCircle,
    Star
} from 'lucide-react';

interface SubscriptionProps {
    subscription?: {
        id: number;
        plan: string;
        status: string;
        stripe_customer_id?: string;
        stripe_subscription_id?: string;
        current_period_start?: string;
        current_period_end?: string;
        trial_ends_at?: string;
        cancelled_at?: string;
        resumes_limit: number;
        resumes_used: number;
        remaining_resumes: number;
        usage_percentage: number;
        features: string[];
        is_active: boolean;
        is_expired: boolean;
    };
    available_plans: Record<string, {
        name: string;
        description: string;
        price: number;
        currency: string;
        billing_period: string;
        stripe_price_id: string;
        features: string[];
        limits: {
            monthly_analyses: number;
            exports_per_month: number;
            collaboration_shares: number;
            api_calls_per_month?: number;
        };
        trial_days: number;
        popular?: boolean;
    }>;
    usage_stats?: {
        period: string;
        start_date: string;
        end_date: string;
        usage: {
            analyses_performed: number;
            exports_generated: number;
            api_calls_made: number;
            collaboration_shares: number;
        };
        limits: {
            monthly_analyses: number;
            exports_per_month: number;
            collaboration_shares: number;
        };
        subscription?: {
            plan: string;
            status: string;
            current_period_start: string;
            current_period_end: string;
        };
    };
}

export default function SubscriptionIndex({ subscription, available_plans, usage_stats }: SubscriptionProps) {
    const [billingCycle, setBillingCycle] = useState<'monthly' | 'yearly'>('monthly');
    const [showCancelDialog, setShowCancelDialog] = useState(false);
    const [cancelImmediately, setCancelImmediately] = useState(false);
    const [isProcessingCheckout, setIsProcessingCheckout] = useState(false);

    const { post, processing } = useForm();

    const plans = Object.entries(available_plans);
    const currentPlan = subscription?.plan || 'free';

    const handleStripeCheckout = async (planId: string) => {
        setIsProcessingCheckout(true);

        try {
            const response = await fetch(route('subscription.checkout'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    plan: planId,
                    upgrade: subscription ? true : false,
                }),
            });

            const data = await response.json();

            if (data.checkout_url) {
                window.location.href = data.checkout_url;
            } else {
                console.error('Checkout failed:', data.error);
                alert(data.error || 'Failed to create checkout session');
            }
        } catch (error) {
            console.error('Checkout error:', error);
            alert('Failed to process checkout');
        } finally {
            setIsProcessingCheckout(false);
        }
    };

    const handleManageBilling = async () => {
        try {
            const response = await fetch(route('subscription.portal'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.portal_url) {
                window.location.href = data.portal_url;
            } else {
                console.error('Portal failed:', data.error);
                alert(data.error || 'Failed to create portal session');
            }
        } catch (error) {
            console.error('Portal error:', error);
            alert('Failed to open billing portal');
        }
    };

    const handlePlanChange = (planName: string) => {
        // Legacy method for non-Stripe plans
        post(route('subscription.change-plan'), {
            data: { plan: planName, billing_cycle: billingCycle },
        });
    };

    const handleCancelSubscription = async () => {
        try {
            const response = await fetch(route('subscription.cancel-subscription'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    immediately: cancelImmediately,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                setShowCancelDialog(false);
                alert(data.message);
                window.location.reload();
            } else {
                console.error('Cancellation failed:', data.error);
                alert(data.error || 'Failed to cancel subscription');
            }
        } catch (error) {
            console.error('Cancellation error:', error);
            alert('Failed to cancel subscription');
        }
    };

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            active: { color: 'bg-slate-100 text-slate-800 dark:bg-slate-900/20 dark:text-slate-400', label: 'Active' },
            cancelled: { color: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400', label: 'Cancelled' },
            expired: { color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', label: 'Expired' },
            suspended: { color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400', label: 'Suspended' },
        };

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.expired;
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.color}`}>
                {config.label}
            </span>
        );
    };

    return (
        <>
            <Head title="Subscription" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        <Button variant="ghost" onClick={() => router.get('/dashboard')} className="mb-4">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Dashboard
                        </Button>

                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            Subscription Management
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Manage your subscription plan and billing preferences
                        </p>
                    </div>

                    {/* Current Subscription */}
                    {subscription && (
                        <Card className="p-8 mb-8">
                            <div className="flex items-center justify-between mb-6">
                                <div className="flex items-center space-x-3">
                                    <Crown className="h-6 w-6 text-slate-600" />
                                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                                        Current Plan
                                    </h2>
                                </div>
                                {getStatusBadge(subscription.status)}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        Plan
                                    </h3>
                                    <p className="text-lg font-semibold text-gray-900 dark:text-white capitalize">
                                        {available_plans[subscription.plan]?.name || subscription.plan}
                                    </p>
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        {subscription.cancelled_at ? 'Ends On' : 'Renewal Date'}
                                    </h3>
                                    <p className="text-lg font-semibold text-gray-900 dark:text-white">
                                        {subscription.current_period_end
                                            ? new Date(subscription.current_period_end).toLocaleDateString()
                                            : 'N/A'
                                        }
                                    </p>
                                    {subscription.trial_ends_at && (
                                        <p className="text-sm text-blue-500">
                                            Trial ends {new Date(subscription.trial_ends_at).toLocaleDateString()}
                                        </p>
                                    )}
                                    {subscription.cancelled_at && (
                                        <p className="text-sm text-red-500">
                                            Cancelled {new Date(subscription.cancelled_at).toLocaleDateString()}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        Usage This Period
                                    </h3>
                                    {usage_stats ? (
                                        <div>
                                            <p className="text-lg font-semibold text-gray-900 dark:text-white">
                                                {usage_stats.usage.analyses_performed} / {usage_stats.limits.monthly_analyses === -1 ? '∞' : usage_stats.limits.monthly_analyses}
                                            </p>
                                            <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                                                <div
                                                    className={`h-2 rounded-full ${
                                                        usage_stats.limits.monthly_analyses !== -1 &&
                                                        (usage_stats.usage.analyses_performed / usage_stats.limits.monthly_analyses) * 100 >= 90
                                                            ? 'bg-red-500'
                                                            : usage_stats.limits.monthly_analyses !== -1 &&
                                                              (usage_stats.usage.analyses_performed / usage_stats.limits.monthly_analyses) * 100 >= 75
                                                            ? 'bg-yellow-500'
                                                            : 'bg-slate-600'
                                                    }`}
                                                    style={{
                                                        width: usage_stats.limits.monthly_analyses === -1
                                                            ? '100%'
                                                            : `${Math.min((usage_stats.usage.analyses_performed / usage_stats.limits.monthly_analyses) * 100, 100)}%`
                                                    }}
                                                ></div>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="text-lg font-semibold text-gray-900 dark:text-white">
                                            {subscription.resumes_used || 0} / {subscription.resumes_limit === -1 ? '∞' : subscription.resumes_limit}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {subscription.is_active && (
                                <div className="flex justify-end space-x-4">
                                    {subscription.stripe_customer_id && (
                                        <Button
                                            variant="outline"
                                            onClick={handleManageBilling}
                                            className="border-slate-300 hover:bg-slate-50 dark:border-slate-600 dark:hover:bg-slate-800"
                                        >
                                            <CreditCard className="h-4 w-4 mr-2" />
                                            Manage Billing
                                        </Button>
                                    )}
                                    <Button
                                        variant="outline"
                                        onClick={() => setShowCancelDialog(true)}
                                        className="text-red-600 border-red-300 hover:bg-red-50 dark:text-red-400 dark:border-red-700 dark:hover:bg-red-900/20"
                                    >
                                        Cancel Subscription
                                    </Button>
                                </div>
                            )}

                            {usage_stats && usage_stats.limits.monthly_analyses !== -1 &&
                             usage_stats.usage.analyses_performed >= usage_stats.limits.monthly_analyses && (
                                <div className="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                    <div className="flex items-start">
                                        <AlertCircle className="h-5 w-5 text-amber-600 dark:text-amber-400 mt-0.5 mr-3" />
                                        <div>
                                            <h3 className="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                                Usage Limit Reached
                                            </h3>
                                            <p className="text-sm text-amber-700 dark:text-amber-300 mt-1">
                                                You've reached your monthly analysis limit. Upgrade your plan to continue analyzing resumes.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </Card>
                    )}

                    {/* Billing Cycle Toggle */}
                    <div className="flex justify-center mb-8">
                        <div className="bg-white dark:bg-gray-800 rounded-lg p-1 border border-gray-200 dark:border-gray-700">
                            <button
                                onClick={() => setBillingCycle('monthly')}
                                className={`px-6 py-2 rounded-md text-sm font-medium transition-colors ${
                                    billingCycle === 'monthly'
                                        ? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200'
                                        : 'text-gray-600 dark:text-gray-400'
                                }`}
                            >
                                Monthly
                            </button>
                            <button
                                onClick={() => setBillingCycle('yearly')}
                                className={`px-6 py-2 rounded-md text-sm font-medium transition-colors ${
                                    billingCycle === 'yearly'
                                        ? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200'
                                        : 'text-gray-600 dark:text-gray-400'
                                }`}
                            >
                                Yearly
                                <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    Save 20%
                                </span>
                            </button>
                        </div>
                    </div>

                    {/* Pricing Plans */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                        {plans.map(([planId, plan]) => {
                            const isCurrent = currentPlan === planId;
                            return (
                            <Card
                                key={planId}
                                className={`relative p-8 ${
                                    plan.popular
                                        ? 'border-2 border-slate-500 shadow-xl'
                                        : isCurrent
                                        ? 'border-2 border-slate-500 shadow-lg'
                                        : 'border border-gray-200 dark:border-gray-700'
                                }`}
                            >
                                {plan.popular && (
                                    <div className="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                        <span className="bg-slate-700 text-white px-4 py-1 rounded-full text-sm font-medium">
                                            Most Popular
                                        </span>
                                    </div>
                                )}

                                {isCurrent && (
                                    <div className="absolute top-4 right-4">
                                        <span className="bg-slate-100 text-slate-800 dark:bg-slate-900/20 dark:text-slate-400 px-2 py-1 rounded-full text-xs font-medium">
                                            Current Plan
                                        </span>
                                    </div>
                                )}

                                <div className="text-center mb-8">
                                    <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                        {plan.name}
                                    </h3>
                                    <div className="mb-4">
                                        <span className="text-4xl font-bold text-gray-900 dark:text-white">
                                            ${plan.price}
                                        </span>
                                        <span className="text-gray-600 dark:text-gray-400">
                                            /{plan.billing_period}
                                        </span>
                                    </div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        {plan.description}
                                    </p>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        {plan.limits.monthly_analyses === -1 ? 'Unlimited' : plan.limits.monthly_analyses} analyses per month
                                    </p>
                                    {plan.trial_days > 0 && !isCurrent && (
                                        <p className="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                            {plan.trial_days} day free trial
                                        </p>
                                    )}
                                </div>

                                <ul className="space-y-3 mb-8">
                                    {plan.features.map((feature, index) => (
                                        <li key={index} className="flex items-start">
                                            <Check className="h-5 w-5 text-slate-500 mr-3 mt-0.5 flex-shrink-0" />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">
                                                {feature}
                                            </span>
                                        </li>
                                    ))}
                                </ul>

                                <Button
                                    onClick={() => handleStripeCheckout(planId)}
                                    disabled={isCurrent || isProcessingCheckout}
                                    className={`w-full ${
                                        isCurrent
                                            ? 'bg-slate-100 text-slate-800 dark:bg-slate-900/20 dark:text-slate-400 cursor-not-allowed'
                                            : plan.popular
                                            ? 'bg-slate-700 hover:bg-slate-800 text-white'
                                            : 'bg-white hover:bg-gray-50 text-gray-900 border border-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-white dark:border-gray-600'
                                    }`}
                                >
                                    {isCurrent ? (
                                        <>
                                            <Check className="h-4 w-4 mr-2" />
                                            Current Plan
                                        </>
                                    ) : (
                                        <>
                                            <Crown className="h-4 w-4 mr-2" />
                                            {subscription ? `Upgrade to ${plan.name}` : `Start with ${plan.name}`}
                                        </>
                                    )}
                                </Button>
                            </Card>
                        );})}
                    </div>

                    {/* Features Comparison */}
                    <Card className="p-8">
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6 text-center">
                            Feature Comparison
                        </h2>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-200 dark:border-gray-700">
                                        <th className="text-left py-4 pr-4 font-medium text-gray-900 dark:text-white">
                                            Features
                                        </th>
                                        {plans.map(([planId, plan]) => (
                                            <th key={planId} className="text-center py-4 px-4 font-medium text-gray-900 dark:text-white">
                                                {plan.name}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[
                                        { name: 'Resume Analysis', starter: true, professional: true, enterprise: true },
                                        { name: 'ATS Optimization', starter: true, professional: true, enterprise: true },
                                        { name: 'Job Match Score', starter: false, professional: true, enterprise: true },
                                        { name: 'Advanced AI Insights', starter: false, professional: true, enterprise: true },
                                        { name: 'Collaboration Features', starter: false, professional: true, enterprise: true },
                                        { name: 'API Access', starter: false, professional: false, enterprise: true },
                                        { name: 'Custom Branding', starter: false, professional: false, enterprise: true },
                                        { name: 'Team Management', starter: false, professional: false, enterprise: true },
                                        { name: 'Priority Support', starter: false, professional: true, enterprise: true },
                                        { name: 'Advanced Analytics', starter: false, professional: false, enterprise: true },
                                    ].map((feature) => (
                                        <tr key={feature.name} className="border-b border-gray-200 dark:border-gray-700">
                                            <td className="py-4 pr-4 text-gray-900 dark:text-white">
                                                {feature.name}
                                            </td>
                                            {plans.map(([planId, plan]) => (
                                                <td key={planId} className="text-center py-4 px-4">
                                                    {feature[planId as keyof typeof feature] ? (
                                                        <Check className="h-5 w-5 text-slate-500 mx-auto" />
                                                    ) : (
                                                        <X className="h-5 w-5 text-gray-300 mx-auto" />
                                                    )}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    {/* Cancel Dialog */}
                    {showCancelDialog && (
                        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                            <Card className="max-w-md w-full p-8">
                                <div className="text-center mb-6">
                                    <AlertCircle className="h-12 w-12 text-red-500 mx-auto mb-4" />
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        Cancel Subscription?
                                    </h3>
                                    <p className="text-gray-600 dark:text-gray-400 mb-4">
                                        Choose when you'd like your subscription to end. You can cancel immediately or at the end of your billing period.
                                    </p>

                                    <div className="space-y-3 mb-6">
                                        <label className="flex items-center space-x-3">
                                            <input
                                                type="radio"
                                                name="cancelType"
                                                checked={!cancelImmediately}
                                                onChange={() => setCancelImmediately(false)}
                                                className="form-radio"
                                            />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">
                                                Cancel at end of billing period (recommended)
                                            </span>
                                        </label>
                                        <label className="flex items-center space-x-3">
                                            <input
                                                type="radio"
                                                name="cancelType"
                                                checked={cancelImmediately}
                                                onChange={() => setCancelImmediately(true)}
                                                className="form-radio"
                                            />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">
                                                Cancel immediately (no refund)
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div className="flex space-x-3">
                                    <Button
                                        variant="outline"
                                        onClick={() => setShowCancelDialog(false)}
                                        className="flex-1"
                                        disabled={processing}
                                    >
                                        Keep Subscription
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        onClick={handleCancelSubscription}
                                        className="flex-1"
                                        disabled={processing}
                                    >
                                        {cancelImmediately ? 'Cancel Now' : 'Cancel at Period End'}
                                    </Button>
                                </div>
                            </Card>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}