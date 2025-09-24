<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Resume;
use App\Models\AnalysisResult;
use App\Models\UserSubscription;
use App\Models\UserAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $analytics = [
            'overview' => $this->getOverviewMetrics($dateRange),
            'user_growth' => $this->getUserGrowthData($dateRange),
            'revenue_analytics' => $this->getRevenueAnalytics($dateRange),
            'feature_usage' => $this->getFeatureUsageStats($dateRange),
            'geographic_data' => $this->getGeographicData($dateRange),
            'conversion_funnel' => $this->getConversionFunnel($dateRange),
        ];

        return Inertia::render('Admin/Analytics/Index', [
            'analytics' => $analytics,
            'date_range' => $dateRange,
        ]);
    }

    /**
     * Display user analytics
     */
    public function users(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $userAnalytics = [
            'user_acquisition' => $this->getUserAcquisitionData($dateRange),
            'user_retention' => $this->getUserRetentionData($dateRange),
            'user_engagement' => $this->getUserEngagementData($dateRange),
            'user_segments' => $this->getUserSegmentData($dateRange),
            'churn_analysis' => $this->getChurnAnalysis($dateRange),
        ];

        return Inertia::render('Admin/Analytics/Users', [
            'analytics' => $userAnalytics,
            'date_range' => $dateRange,
        ]);
    }

    /**
     * Display revenue analytics
     */
    public function revenue(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        $revenueAnalytics = [
            'revenue_overview' => $this->getRevenueOverview($dateRange),
            'subscription_metrics' => $this->getSubscriptionMetrics($dateRange),
            'revenue_by_plan' => $this->getRevenueByPlan($dateRange),
            'mrr_growth' => $this->getMRRGrowth($dateRange),
            'ltv_analysis' => $this->getLTVAnalysis($dateRange),
            'payment_analytics' => $this->getPaymentAnalytics($dateRange),
        ];

        return Inertia::render('Admin/Analytics/Revenue', [
            'analytics' => $revenueAnalytics,
            'date_range' => $dateRange,
        ]);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request, string $type)
    {
        $request->validate([
            'format' => 'required|in:csv,xlsx,pdf',
        ]);

        $dateRange = $this->getDateRange($request);
        $format = $request->format;

        switch ($type) {
            case 'users':
                return $this->exportUserAnalytics($dateRange, $format);
            case 'revenue':
                return $this->exportRevenueAnalytics($dateRange, $format);
            case 'resumes':
                return $this->exportResumeAnalytics($dateRange, $format);
            default:
                return back()->with('error', 'Invalid export type');
        }
    }

    // Private helper methods

    private function getDateRange(Request $request): array
    {
        $period = $request->get('period', '30d');

        switch ($period) {
            case '7d':
                $start = now()->subDays(7);
                break;
            case '30d':
                $start = now()->subDays(30);
                break;
            case '90d':
                $start = now()->subDays(90);
                break;
            case '1y':
                $start = now()->subYear();
                break;
            case 'custom':
                $start = $request->get('start_date') ? Carbon::parse($request->start_date) : now()->subDays(30);
                break;
            default:
                $start = now()->subDays(30);
        }

        return [
            'start' => $start,
            'end' => now(),
            'period' => $period,
        ];
    }

    private function getOverviewMetrics(array $dateRange): array
    {
        $currentPeriodStart = $dateRange['start'];
        $currentPeriodEnd = $dateRange['end'];

        // Calculate previous period for comparison
        $periodLength = $currentPeriodEnd->diffInDays($currentPeriodStart);
        $previousPeriodStart = $currentPeriodStart->copy()->subDays($periodLength);
        $previousPeriodEnd = $currentPeriodStart->copy();

        $current = [
            'total_users' => User::whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])->count(),
            'total_resumes' => Resume::whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])->count(),
            'total_analyses' => AnalysisResult::whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])->count(),
            'active_subscriptions' => UserSubscription::where('status', 'active')
                ->whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])
                ->count(),
        ];

        $previous = [
            'total_users' => User::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count(),
            'total_resumes' => Resume::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count(),
            'total_analyses' => AnalysisResult::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count(),
            'active_subscriptions' => UserSubscription::where('status', 'active')
                ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
                ->count(),
        ];

        // Calculate growth percentages
        $growth = [];
        foreach ($current as $key => $value) {
            $previousValue = $previous[$key] ?? 0;
            $growth[$key] = $previousValue > 0 ? round((($value - $previousValue) / $previousValue) * 100, 2) : 0;
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'growth' => $growth,
        ];
    }

    private function getUserGrowthData(array $dateRange): array
    {
        $days = $dateRange['end']->diffInDays($dateRange['start']);
        $interval = $days > 90 ? 'week' : 'day';

        $query = User::select(
            DB::raw("DATE({$this->getDateGrouping($interval)}) as period"),
            DB::raw('COUNT(*) as new_users')
        )
        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->groupBy('period')
        ->orderBy('period');

        return $query->get()->map(function ($item) {
            return [
                'date' => $item->period,
                'new_users' => $item->new_users,
                'cumulative_users' => User::where('created_at', '<=', $item->period)->count(),
            ];
        })->toArray();
    }

    private function getRevenueAnalytics(array $dateRange): array
    {
        $planPricing = [
            'starter' => 9.99,
            'professional' => 19.99,
            'enterprise' => 49.99,
            'basic' => 9.99,
            'pro' => 29.99,
        ];

        $subscriptions = UserSubscription::where('status', 'active')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        $totalRevenue = $subscriptions->sum(function ($subscription) use ($planPricing) {
            return $planPricing[$subscription->plan] ?? 0;
        });

        $revenueByPlan = $subscriptions->groupBy('plan')->map(function ($group, $plan) use ($planPricing) {
            $count = $group->count();
            $revenue = $count * ($planPricing[$plan] ?? 0);
            return [
                'plan' => $plan,
                'subscribers' => $count,
                'revenue' => $revenue,
            ];
        })->values();

        return [
            'total_revenue' => $totalRevenue,
            'revenue_by_plan' => $revenueByPlan,
            'average_revenue_per_user' => $subscriptions->count() > 0 ? $totalRevenue / $subscriptions->count() : 0,
        ];
    }

    private function getFeatureUsageStats(array $dateRange): array
    {
        return [
            'resume_uploads' => Resume::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'ai_analyses' => AnalysisResult::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'exports' => 0, // Would track exports in separate table
            'shares' => 0,  // Would track shares in separate table
        ];
    }

    private function getGeographicData(array $dateRange): array
    {
        // This would require storing user location data
        return [
            'countries' => [
                ['country' => 'United States', 'users' => 150, 'percentage' => 45.5],
                ['country' => 'Canada', 'users' => 80, 'percentage' => 24.2],
                ['country' => 'United Kingdom', 'users' => 60, 'percentage' => 18.2],
                ['country' => 'Australia', 'users' => 40, 'percentage' => 12.1],
            ]
        ];
    }

    private function getConversionFunnel(array $dateRange): array
    {
        $totalVisitors = 1000; // Would track with analytics
        $signups = User::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $resumeUploads = Resume::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $subscriptions = UserSubscription::where('status', 'active')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        return [
            ['stage' => 'Visitors', 'count' => $totalVisitors, 'percentage' => 100],
            ['stage' => 'Sign ups', 'count' => $signups, 'percentage' => round(($signups / $totalVisitors) * 100, 2)],
            ['stage' => 'Resume Uploads', 'count' => $resumeUploads, 'percentage' => round(($resumeUploads / $signups) * 100, 2)],
            ['stage' => 'Subscriptions', 'count' => $subscriptions, 'percentage' => round(($subscriptions / $signups) * 100, 2)],
        ];
    }

    private function getUserAcquisitionData(array $dateRange): array
    {
        // This would track acquisition channels
        return [
            'channels' => [
                ['channel' => 'Organic Search', 'users' => 120, 'percentage' => 40],
                ['channel' => 'Direct', 'users' => 90, 'percentage' => 30],
                ['channel' => 'Social Media', 'users' => 60, 'percentage' => 20],
                ['channel' => 'Referral', 'users' => 30, 'percentage' => 10],
            ]
        ];
    }

    private function getUserRetentionData(array $dateRange): array
    {
        // This would calculate actual retention cohorts
        return [
            'retention_by_cohort' => [
                ['cohort' => '2024-01', 'week_1' => 85, 'week_2' => 70, 'week_4' => 60, 'week_8' => 50],
                ['cohort' => '2024-02', 'week_1' => 88, 'week_2' => 72, 'week_4' => 62, 'week_8' => 52],
            ]
        ];
    }

    private function getUserEngagementData(array $dateRange): array
    {
        // This would track user engagement metrics
        return [
            'daily_active_users' => 250,
            'weekly_active_users' => 800,
            'monthly_active_users' => 2400,
            'average_session_duration' => '12:30',
            'average_resumes_per_user' => 2.3,
        ];
    }

    private function getUserSegmentData(array $dateRange): array
    {
        $totalUsers = User::count();

        return [
            'by_plan' => UserSubscription::select('plan', DB::raw('COUNT(*) as count'))
                ->groupBy('plan')
                ->get()
                ->map(function ($item) use ($totalUsers) {
                    return [
                        'segment' => $item->plan,
                        'users' => $item->count,
                        'percentage' => round(($item->count / $totalUsers) * 100, 2),
                    ];
                })->toArray(),
            'by_activity' => [
                ['segment' => 'Highly Active', 'users' => 300, 'percentage' => 25],
                ['segment' => 'Moderately Active', 'users' => 600, 'percentage' => 50],
                ['segment' => 'Low Activity', 'users' => 300, 'percentage' => 25],
            ],
        ];
    }

    private function getChurnAnalysis(array $dateRange): array
    {
        $churnedUsers = UserSubscription::where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        $totalUsers = UserSubscription::count();

        return [
            'churn_rate' => $totalUsers > 0 ? round(($churnedUsers / $totalUsers) * 100, 2) : 0,
            'churned_users' => $churnedUsers,
            'retention_rate' => $totalUsers > 0 ? round((($totalUsers - $churnedUsers) / $totalUsers) * 100, 2) : 0,
        ];
    }

    private function getRevenueOverview(array $dateRange): array
    {
        // This would calculate actual revenue metrics
        return [
            'total_revenue' => 15420.50,
            'mrr' => 5140.17,
            'arr' => 61682.00,
            'average_revenue_per_user' => 64.25,
        ];
    }

    private function getSubscriptionMetrics(array $dateRange): array
    {
        return [
            'new_subscriptions' => UserSubscription::where('status', 'active')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'cancelled_subscriptions' => UserSubscription::where('status', 'cancelled')
                ->whereBetween('cancelled_at', [$dateRange['start'], $dateRange['end']])
                ->count(),
            'upgrade_rate' => 15.2,
            'downgrade_rate' => 3.8,
        ];
    }

    private function getRevenueByPlan(array $dateRange): array
    {
        // This would calculate revenue by plan
        return [
            ['plan' => 'Starter', 'revenue' => 2500.00, 'subscribers' => 250],
            ['plan' => 'Professional', 'revenue' => 8000.00, 'subscribers' => 400],
            ['plan' => 'Enterprise', 'revenue' => 4920.50, 'subscribers' => 98],
        ];
    }

    private function getMRRGrowth(array $dateRange): array
    {
        // This would track MRR over time
        return [];
    }

    private function getLTVAnalysis(array $dateRange): array
    {
        return [
            'average_ltv' => 450.00,
            'ltv_by_plan' => [
                'starter' => 120.00,
                'professional' => 580.00,
                'enterprise' => 1200.00,
            ],
        ];
    }

    private function getPaymentAnalytics(array $dateRange): array
    {
        return [
            'successful_payments' => 98.5,
            'failed_payments' => 1.5,
            'average_payment_value' => 24.99,
        ];
    }

    private function getDateGrouping(string $interval): string
    {
        switch ($interval) {
            case 'hour':
                return "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')";
            case 'day':
                return "DATE(created_at)";
            case 'week':
                return "DATE_FORMAT(created_at, '%Y-%u')";
            case 'month':
                return "DATE_FORMAT(created_at, '%Y-%m')";
            default:
                return "DATE(created_at)";
        }
    }

    private function exportUserAnalytics(array $dateRange, string $format): \Symfony\Component\HttpFoundation\Response
    {
        // Implementation would export user analytics data
        return response()->download('user_analytics.csv');
    }

    private function exportRevenueAnalytics(array $dateRange, string $format): \Symfony\Component\HttpFoundation\Response
    {
        // Implementation would export revenue analytics data
        return response()->download('revenue_analytics.csv');
    }

    private function exportResumeAnalytics(array $dateRange, string $format): \Symfony\Component\HttpFoundation\Response
    {
        // Implementation would export resume analytics data
        return response()->download('resume_analytics.csv');
    }
}