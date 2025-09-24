import { Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Progress } from '@/components/ui/Progress';
import { Crown, AlertTriangle, CheckCircle } from 'lucide-react';

interface SubscriptionCardProps {
    subscription?: {
        plan: string;
        status: string;
        resumes_limit: number;
        resumes_used: number;
        remaining_resumes: number;
        usage_percentage: number;
        period_ends_at: string;
        days_remaining: number;
        can_upload: boolean;
        features: string[];
        is_active: boolean;
        is_expired: boolean;
    } | null;
}

export function SubscriptionCard({ subscription }: SubscriptionCardProps) {
    if (!subscription) {
        return (
            <Card className="border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20">
                <CardHeader>
                    <CardTitle className="flex items-center space-x-2 text-yellow-800 dark:text-yellow-200">
                        <AlertTriangle className="h-5 w-5" />
                        <span>No Active Subscription</span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-yellow-700 dark:text-yellow-300 mb-4">
                        You don't have an active subscription. Upgrade to unlock all features.
                    </p>
                    <Button asChild>
                        <Link href="/subscription/upgrade">
                            Choose a Plan
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        );
    }

    const getStatusBadge = () => {
        if (!subscription.is_active) {
            return <Badge variant="destructive">Inactive</Badge>;
        }
        if (subscription.is_expired) {
            return <Badge variant="destructive">Expired</Badge>;
        }
        if (subscription.days_remaining <= 7) {
            return <Badge variant="warning">Expires Soon</Badge>;
        }
        return <Badge variant="success">Active</Badge>;
    };

    const getPlanIcon = () => {
        const icons = {
            free: '🆓',
            basic: '📋',
            pro: '⭐',
            enterprise: '👑',
        };
        return icons[subscription.plan as keyof typeof icons] || '📄';
    };

    const getUsageColor = () => {
        if (subscription.usage_percentage >= 90) return 'text-red-600';
        if (subscription.usage_percentage >= 70) return 'text-yellow-600';
        return 'text-green-600';
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                        <span className="text-lg">{getPlanIcon()}</span>
                        <span className="capitalize">{subscription.plan} Plan</span>
                    </div>
                    {getStatusBadge()}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                {/* Usage Statistics */}
                <div>
                    <div className="flex justify-between items-center mb-2">
                        <span className="text-sm font-medium">Resume Usage</span>
                        <span className={`text-sm font-medium ${getUsageColor()}`}>
                            {subscription.resumes_used} / {subscription.resumes_limit === -1 ? '∞' : subscription.resumes_limit}
                        </span>
                    </div>
                    {subscription.resumes_limit !== -1 && (
                        <Progress
                            value={subscription.usage_percentage}
                            className="h-2"
                        />
                    )}
                    <div className="flex justify-between text-xs text-gray-500 mt-1">
                        <span>
                            {subscription.remaining_resumes === -1
                                ? 'Unlimited remaining'
                                : `${subscription.remaining_resumes} remaining`
                            }
                        </span>
                        <span>{Math.round(subscription.usage_percentage)}% used</span>
                    </div>
                </div>

                {/* Plan Features */}
                <div>
                    <h4 className="text-sm font-medium mb-2">Plan Features</h4>
                    <div className="space-y-1">
                        {subscription.features.slice(0, 3).map((feature, index) => (
                            <div key={index} className="flex items-center space-x-2 text-xs">
                                <CheckCircle className="h-3 w-3 text-green-500" />
                                <span className="capitalize">{feature.replace('_', ' ')}</span>
                            </div>
                        ))}
                        {subscription.features.length > 3 && (
                            <div className="text-xs text-gray-500">
                                +{subscription.features.length - 3} more features
                            </div>
                        )}
                    </div>
                </div>

                {/* Subscription Details */}
                <div className="pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div className="text-xs text-gray-500 space-y-1">
                        <div>Expires: {new Date(subscription.period_ends_at).toLocaleDateString()}</div>
                        <div>{subscription.days_remaining} days remaining</div>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex space-x-2 pt-2">
                    <Button variant="outline" size="sm" asChild className="flex-1">
                        <Link href="/subscription">
                            Manage Plan
                        </Link>
                    </Button>
                    {subscription.plan !== 'enterprise' && (
                        <Button size="sm" asChild className="flex-1">
                            <Link href="/subscription/upgrade">
                                Upgrade
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Upload Status */}
                {!subscription.can_upload && (
                    <div className="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <div className="flex items-center space-x-2">
                            <AlertTriangle className="h-4 w-4 text-red-600" />
                            <span className="text-sm text-red-700 dark:text-red-300">
                                You've reached your resume limit. Upgrade to continue.
                            </span>
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}