<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get default tenant for demo purposes
        $tenant = $this->getDefaultTenant($request);

        // Get dashboard statistics
        $stats = [
            'total_resumes' => $user->resumes()->count(),
            'analyzed_resumes' => $user->resumes()->where('analysis_status', 'completed')->count(),
            'average_score' => $this->getAverageScore($user),
            'recent_uploads' => $user->resumes()->where('created_at', '>=', now()->subDays(7))->count(),
            'processing_resumes' => $user->resumes()->where('analysis_status', 'processing')->count(),
            'failed_analyses' => $user->resumes()->where('analysis_status', 'failed')->count(),
        ];

        // Get recent resumes with their latest analysis
        $recent_resumes = $user->resumes()
            ->with('analysisResults')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($resume) {
                $latestAnalysis = $resume->latestAnalysis();
                return [
                    'id' => $resume->id,
                    'original_filename' => $resume->original_filename,
                    'analysis_status' => $resume->analysis_status,
                    'parsing_status' => $resume->parsing_status,
                    'created_at' => $resume->created_at,
                    'file_size_human' => $resume->file_size_human,
                    'latest_analysis' => $latestAnalysis ? [
                        'overall_score' => $latestAnalysis->overall_score,
                        'overall_grade' => $latestAnalysis->overall_grade,
                        'ats_score' => $latestAnalysis->ats_score,
                        'content_score' => $latestAnalysis->content_score,
                        'format_score' => $latestAnalysis->format_score,
                        'keyword_score' => $latestAnalysis->keyword_score,
                    ] : null,
                ];
            });

        // Get subscription information
        $subscription = $user->activeSubscription;
        $subscriptionData = null;
        if ($subscription) {
            $subscriptionData = [
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'resumes_limit' => $subscription->resumes_limit,
                'resumes_used' => $subscription->resumes_used,
                'remaining_resumes' => $subscription->remaining_resumes,
                'usage_percentage' => $subscription->usage_percentage,
                'period_ends_at' => $subscription->period_ends_at,
                'days_remaining' => $subscription->days_remaining,
                'can_upload' => $subscription->canUploadResume(),
            ];
        }

        // Get weekly analytics
        $weeklyAnalytics = \App\Models\UserAnalytics::getWeeklyStats($user->id);

        // Get activity feed (recent activities)
        $recentActivity = $user->activityLogs()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'created_at' => $log->created_at,
                    'properties' => $log->properties,
                ];
            });

        return Inertia::render('Dashboard', [
            'tenant' => [
                'name' => $tenant->name ?? 'AI Resume Analyzer',
                'plan' => $tenant->plan ?? 'professional',
                'branding' => is_callable([$tenant, 'getBrandingData']) ? $tenant->getBrandingData() : [],
            ],
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'initials' => $user->initials,
                'role' => $user->role,
                'current_plan' => $user->getCurrentPlan(),
            ],
            'stats' => $stats,
            'recent_resumes' => $recent_resumes,
            'subscription' => $subscriptionData,
            'weekly_analytics' => $weeklyAnalytics,
            'recent_activity' => $recentActivity,
        ]);
    }

    private function getDefaultTenant($request)
    {
        // For demo purposes, try to get tenant from URL parameter or use demo-corp
        $tenantId = $request->get('tenant', 'demo-corp');

        try {
            $tenant = \App\Models\Tenant::find($tenantId);

            if ($tenant) {
                // Only set as current if not already current to avoid recursion
                if (!app()->bound('currentTenant')) {
                    app()->instance('currentTenant', $tenant);
                }
                return $tenant;
            }
        } catch (\Exception $e) {
            // Log the error but continue with fallback
            \Log::warning('Failed to load tenant: ' . $e->getMessage());
        }

        // Return a fallback tenant object
        return (object) [
            'name' => 'AI Resume Analyzer',
            'plan' => 'enterprise',
            'getBrandingData' => function() { return []; },
        ];
    }

    private function getAverageScore(User $user): int
    {
        $analyzedResumes = $user->resumes()
            ->whereHas('analysisResults')
            ->with('analysisResults')
            ->get();

        if ($analyzedResumes->isEmpty()) {
            return 0;
        }

        $totalScore = $analyzedResumes->sum(function ($resume) {
            $latestAnalysis = $resume->latestAnalysis();
            return $latestAnalysis ? $latestAnalysis->overall_score : 0;
        });

        return round($totalScore / $analyzedResumes->count());
    }
}