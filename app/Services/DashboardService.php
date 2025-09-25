<?php

namespace App\Services;

use App\Models\User;
use App\Models\Resume;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    public function getDashboardData(User $user): array
    {
        return [
            'stats' => $this->getUserStats($user),
            'recent_resumes' => $this->getRecentResumes($user),
            'analytics' => $this->getUserAnalytics($user),
            'subscription' => $this->getSubscriptionData($user),
            'notifications' => $this->getRecentNotifications($user),
            'quick_actions' => $this->getQuickActions($user),
        ];
    }

    private function getUserStats(User $user): array
    {
        $totalResumes = $user->resumes()->count();
        $analyzedResumes = $user->resumes()->where('analysis_status', 'completed')->count();
        $averageScore = $this->getAverageScore($user);
        $thisMonthResumes = $user->resumes()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return [
            'total_resumes' => $totalResumes,
            'analyzed_resumes' => $analyzedResumes,
            'average_score' => $averageScore,
            'this_month_resumes' => $thisMonthResumes,
            'completion_rate' => $totalResumes > 0 ? round(($analyzedResumes / $totalResumes) * 100, 1) : 0,
        ];
    }

    private function getAverageScore(User $user): float
    {
        $scores = DB::table('analysis_results')
            ->join('resumes', 'analysis_results.resume_id', '=', 'resumes.id')
            ->where('resumes.user_id', $user->id)
            ->whereNotNull('analysis_results.overall_score')
            ->pluck('analysis_results.overall_score');

        return $scores->count() > 0 ? round($scores->avg(), 1) : 0;
    }

    private function getRecentResumes(User $user, int $limit = 5): array
    {
        return $user->resumes()
            ->with('analysisResults')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($resume) {
                $latestAnalysis = $resume->latestAnalysis();
                
                return [
                    'id' => $resume->id,
                    'original_filename' => $resume->original_filename,
                    'file_size_human' => $resume->file_size_human,
                    'parsing_status' => $resume->parsing_status,
                    'analysis_status' => $resume->analysis_status,
                    'created_at' => $resume->created_at,
                    'latest_analysis' => $latestAnalysis ? [
                        'overall_score' => $latestAnalysis->overall_score,
                        'overall_grade' => $latestAnalysis->overall_grade,
                        'created_at' => $latestAnalysis->created_at,
                    ] : null,
                ];
            })
            ->toArray();
    }

    private function getUserAnalytics(User $user): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        // Score trends over time
        $scoreHistory = DB::table('analysis_results')
            ->join('resumes', 'analysis_results.resume_id', '=', 'resumes.id')
            ->where('resumes.user_id', $user->id)
            ->where('analysis_results.created_at', '>=', $thirtyDaysAgo)
            ->orderBy('analysis_results.created_at')
            ->select(
                'analysis_results.overall_score',
                'analysis_results.ats_score',
                'analysis_results.content_score',
                'analysis_results.format_score',
                'analysis_results.keyword_score',
                'analysis_results.created_at'
            )
            ->get()
            ->map(function ($result) {
                return [
                    'date' => Carbon::parse($result->created_at)->format('Y-m-d'),
                    'overall_score' => $result->overall_score,
                    'ats_score' => $result->ats_score,
                    'content_score' => $result->content_score,
                    'format_score' => $result->format_score,
                    'keyword_score' => $result->keyword_score,
                ];
            })
            ->toArray();

        // Activity by day
        $activityData = $user->resumes()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'uploads' => $item->count,
                ];
            })
            ->toArray();

        return [
            'score_history' => $scoreHistory,
            'activity_data' => $activityData,
            'improvement_trend' => $this->calculateImprovementTrend($scoreHistory),
        ];
    }

    private function calculateImprovementTrend(array $scoreHistory): array
    {
        if (count($scoreHistory) < 2) {
            return [
                'trend' => 'neutral',
                'percentage' => 0,
                'message' => 'Not enough data to determine trend',
            ];
        }

        $firstScore = $scoreHistory[0]['overall_score'] ?? 0;
        $lastScore = end($scoreHistory)['overall_score'] ?? 0;
        
        $difference = $lastScore - $firstScore;
        $percentage = $firstScore > 0 ? round(($difference / $firstScore) * 100, 1) : 0;

        $trend = $difference > 5 ? 'improving' : ($difference < -5 ? 'declining' : 'stable');
        
        $message = match($trend) {
            'improving' => "Your scores have improved by {$percentage}% over the last 30 days!",
            'declining' => "Your scores have declined by " . abs($percentage) . "% over the last 30 days.",
            default => "Your scores have remained stable over the last 30 days.",
        };

        return [
            'trend' => $trend,
            'percentage' => abs($percentage),
            'message' => $message,
        ];
    }

    private function getSubscriptionData(User $user): array
    {
        $subscription = $user->subscription;

        if (!$subscription) {
            return [
                'plan' => 'free',
                'status' => 'none',
                'features' => $this->getFreePlanFeatures(),
                'usage' => $this->getUsageData($user),
            ];
        }

        return [
            'plan' => $subscription->plan,
            'status' => $subscription->status,
            'current_period_end' => $subscription->current_period_end,
            'features' => $this->getPlanFeatures($subscription->plan),
            'usage' => $this->getUsageData($user),
        ];
    }

    private function getFreePlanFeatures(): array
    {
        return [
            'monthly_analyses' => 3,
            'exports_per_month' => 1,
            'collaboration_shares' => 0,
            'advanced_analysis' => false,
            'api_access' => false,
        ];
    }

    private function getPlanFeatures(string $plan): array
    {
        $features = [
            'starter' => [
                'monthly_analyses' => 25,
                'exports_per_month' => 10,
                'collaboration_shares' => 5,
                'advanced_analysis' => false,
                'api_access' => false,
            ],
            'professional' => [
                'monthly_analyses' => 100,
                'exports_per_month' => 50,
                'collaboration_shares' => 25,
                'advanced_analysis' => true,
                'api_access' => true,
            ],
            'enterprise' => [
                'monthly_analyses' => -1, // unlimited
                'exports_per_month' => -1, // unlimited
                'collaboration_shares' => -1, // unlimited
                'advanced_analysis' => true,
                'api_access' => true,
                'white_label' => true,
                'priority_support' => true,
            ],
        ];

        return $features[$plan] ?? $this->getFreePlanFeatures();
    }

    private function getUsageData(User $user): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return [
            'analyses_this_month' => $user->resumes()
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where('analysis_status', 'completed')
                ->count(),
            'exports_this_month' => 0, // Would track from exports table
            'api_calls_this_month' => 0, // Would track from API usage table
            'collaboration_shares_this_month' => 0, // Would track from shares table
        ];
    }

    private function getRecentNotifications(User $user, int $limit = 5): array
    {
        try {
            return DB::table('notifications')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'data' => json_decode($notification->data, true),
                        'read_at' => $notification->read_at,
                        'created_at' => $notification->created_at,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('Failed to fetch notifications for dashboard', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    private function getQuickActions(User $user): array
    {
        $actions = [
            [
                'id' => 'upload_resume',
                'title' => 'Upload New Resume',
                'description' => 'Get AI-powered analysis of your resume',
                'icon' => 'upload',
                'url' => '/resumes/upload',
                'primary' => true,
            ],
        ];

        // Add conditional actions based on user state
        $recentResume = $user->resumes()->latest()->first();
        
        if ($recentResume && $recentResume->analysis_status === 'completed') {
            $actions[] = [
                'id' => 'view_latest_analysis',
                'title' => 'View Latest Analysis',
                'description' => "See results for {$recentResume->original_filename}",
                'icon' => 'chart',
                'url' => "/resumes/{$recentResume->id}",
                'primary' => false,
            ];
        }

        if (!$user->hasActiveSubscription()) {
            $actions[] = [
                'id' => 'upgrade_plan',
                'title' => 'Upgrade Plan',
                'description' => 'Unlock advanced features and unlimited analyses',
                'icon' => 'star',
                'url' => '/subscription',
                'primary' => false,
            ];
        }

        $actions[] = [
            'id' => 'view_settings',
            'title' => 'Account Settings',
            'description' => 'Manage your profile and preferences',
            'icon' => 'settings',
            'url' => '/settings',
            'primary' => false,
        ];

        return $actions;
    }

    public function getSystemHealthData(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'storage' => $this->checkStorageHealth(),
            'queue' => $this->checkQueueHealth(),
            'cache' => $this->checkCacheHealth(),
        ];
    }

    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'message' => 'Database connection is working properly',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time_ms' => null,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkStorageHealth(): array
    {
        try {
            $diskSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usedPercentage = round(((($totalSpace - $diskSpace) / $totalSpace) * 100), 2);

            $status = $usedPercentage > 90 ? 'critical' : ($usedPercentage > 80 ? 'warning' : 'healthy');

            return [
                'status' => $status,
                'used_percentage' => $usedPercentage,
                'free_space_gb' => round($diskSpace / (1024 * 1024 * 1024), 2),
                'message' => "Storage is {$usedPercentage}% full",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cannot check storage health: ' . $e->getMessage(),
            ];
        }
    }

    private function checkQueueHealth(): array
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $pendingJobs = DB::table('jobs')->count();

            $status = $failedJobs > 10 ? 'warning' : 'healthy';

            return [
                'status' => $status,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'message' => "Queue has {$pendingJobs} pending and {$failedJobs} failed jobs",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cannot check queue health: ' . $e->getMessage(),
            ];
        }
    }

    private function checkCacheHealth(): array
    {
        try {
            $key = 'health_check_' . time();
            $value = 'test_value';

            cache()->put($key, $value, 60);
            $retrieved = cache()->get($key);
            cache()->forget($key);

            $status = $retrieved === $value ? 'healthy' : 'unhealthy';

            return [
                'status' => $status,
                'message' => $status === 'healthy' ? 'Cache is working properly' : 'Cache read/write failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache check failed: ' . $e->getMessage(),
            ];
        }
    }
}