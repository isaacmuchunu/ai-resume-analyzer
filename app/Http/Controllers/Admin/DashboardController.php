<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Resume;
use App\Models\AnalysisResult;
use App\Models\UserSubscription;
use App\Models\UserAnalytics;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // System-wide statistics
        $stats = [
            'total_users' => User::count(),
            'total_resumes' => Resume::count(),
            'total_analyses' => AnalysisResult::count(),
            'active_subscriptions' => UserSubscription::where('status', 'active')->count(),
            'total_tenants' => Tenant::count(),
            'revenue_this_month' => $this->calculateMonthlyRevenue(),
        ];

        // Recent users
        $recent_users = User::with(['subscription', 'resumes'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                    'resumes_count' => $user->resumes->count(),
                    'current_plan' => $user->getCurrentPlan(),
                ];
            });

        // Recent activities
        $recent_activities = \App\Models\ActivityLog::with('causer')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'created_at' => $log->created_at,
                    'user' => $log->causer ? [
                        'name' => $log->causer->full_name ?? 'System',
                        'email' => $log->causer->email ?? null,
                    ] : null,
                    'properties' => $log->properties,
                ];
            });

        // Monthly analytics
        $monthly_analytics = $this->getMonthlyAnalytics();

        // Top performing users
        $top_users = User::whereHas('resumes.analysisResults')
            ->withCount(['resumes' => function ($query) {
                $query->where('analysis_status', 'completed');
            }])
            ->orderBy('resumes_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'resumes_count' => $user->resumes_count,
                    'current_plan' => $user->getCurrentPlan(),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recent_users' => $recent_users,
            'recent_activities' => $recent_activities,
            'monthly_analytics' => $monthly_analytics,
            'top_users' => $top_users,
        ]);
    }

    private function calculateMonthlyRevenue(): float
    {
        $planPricing = [
            'free' => 0,
            'basic' => 9.99,
            'pro' => 29.99,
            'enterprise' => 99.99,
        ];

        return UserSubscription::where('status', 'active')
            ->whereMonth('period_starts_at', now()->month)
            ->whereYear('period_starts_at', now()->year)
            ->get()
            ->sum(function ($subscription) use ($planPricing) {
                return $planPricing[$subscription->plan] ?? 0;
            });
    }

    private function getMonthlyAnalytics(): array
    {
        $last6Months = collect(range(5, 0))->map(function ($monthsBack) {
            $date = Carbon::now()->subMonths($monthsBack);
            $month = $date->format('Y-m');

            return [
                'month' => $date->format('M Y'),
                'users' => User::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'resumes' => Resume::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'analyses' => AnalysisResult::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        });

        return $last6Months->toArray();
    }
}