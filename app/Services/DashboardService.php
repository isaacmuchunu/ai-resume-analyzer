<?php

namespace App\Services;

use App\Models\User;
use App\Models\Resume;
use App\Models\AnalysisResult;
use App\Models\UserAnalytics;
use Illuminate\Support\Collection;

class DashboardService
{
    public function getDashboardData(User $user, array $options = []): array
    {
        $timeRange = $options['time_range'] ?? 30; // days
        $includeAnalytics = $options['include_analytics'] ?? true;
        $includeActivity = $options['include_activity'] ?? true;

        return [
            'stats' => $this->getStats($user, $timeRange),
            'recent_resumes' => $this->getRecentResumes($user, $options['recent_limit'] ?? 5),
            'subscription' => $this->getSubscriptionData($user),
            'analytics' => $includeAnalytics ? $this->getAnalyticsData($user, $timeRange) : null,
            'activity' => $includeActivity ? $this->getRecentActivity($user, $options['activity_limit'] ?? 10) : null,
        ];
    }

    public function getStats(User $user, int $timeRange = 30): array
    {
        $startDate = now()->subDays($timeRange);

        return [
            'total_resumes' => $user->resumes()->count(),
            'analyzed_resumes' => $user->resumes()->where('analysis_status', 'completed')->count(),
            'processing_resumes' => $user->resumes()->where('analysis_status', 'processing')->count(),
            'failed_analyses' => $user->resumes()->where('analysis_status', 'failed')->count(),
            'recent_uploads' => $user->resumes()->where('created_at', '>=', $startDate)->count(),
            'average_score' => $this->getAverageScore($user),
            'best_score' => $this->getBestScore($user),
            'improvement_trend' => $this->getImprovementTrend($user, $timeRange),
        ];
    }

    public function getRecentResumes(User $user, int $limit = 5): Collection
    {
        return $user->resumes()
            ->with(['analysisResults' => function ($query) {
                $query->latest();
            }])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($resume) {
                $latestAnalysis = $resume->analysisResults->first();
                return [
                    'id' => $resume->id,
                    'original_filename' => $resume->original_filename,
                    'analysis_status' => $resume->analysis_status,
                    'parsing_status' => $resume->parsing_status,
                    'created_at' => $resume->created_at,
                    'updated_at' => $resume->updated_at,
                    'file_size_human' => $resume->file_size_human,
                    'file_type' => $resume->file_type,
                    'is_active' => $resume->is_active,
                    'latest_analysis' => $latestAnalysis ? [
                        'id' => $latestAnalysis->id,
                        'overall_score' => $latestAnalysis->overall_score,
                        'overall_grade' => $latestAnalysis->overall_grade,
                        'ats_score' => $latestAnalysis->ats_score,
                        'content_score' => $latestAnalysis->content_score,
                        'format_score' => $latestAnalysis->format_score,
                        'keyword_score' => $latestAnalysis->keyword_score,
                        'created_at' => $latestAnalysis->created_at,
                    ] : null,
                ];
            });
    }

    public function getSubscriptionData(User $user): ?array
    {
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            return null;
        }

        return [
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
            'can_upload' => $subscription->canUploadResume(),
            'features' => $subscription->features,
            'is_active' => $subscription->isActive(),
            'is_expired' => $subscription->isExpired(),
        ];
    }

    public function getAnalyticsData(User $user, int $timeRange = 30): array
    {
        return UserAnalytics::getWeeklyStats($user->id);
    }

    public function getRecentActivity(User $user, int $limit = 10): Collection
    {
        return $user->activityLogs()
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'log_name' => $log->log_name,
                    'created_at' => $log->created_at,
                    'properties' => $log->properties,
                    'subject_type' => $log->subject_type,
                    'subject_id' => $log->subject_id,
                ];
            });
    }

    private function getAverageScore(User $user): int
    {
        $analyzedResumes = $user->resumes()
            ->whereHas('analysisResults')
            ->with(['analysisResults' => function ($query) {
                $query->latest();
            }])
            ->get();

        if ($analyzedResumes->isEmpty()) {
            return 0;
        }

        $totalScore = $analyzedResumes->sum(function ($resume) {
            $latestAnalysis = $resume->analysisResults->first();
            return $latestAnalysis ? $latestAnalysis->overall_score : 0;
        });

        return round($totalScore / $analyzedResumes->count());
    }

    private function getBestScore(User $user): int
    {
        return $user->resumes()
            ->whereHas('analysisResults')
            ->with(['analysisResults' => function ($query) {
                $query->latest();
            }])
            ->get()
            ->map(function ($resume) {
                $latestAnalysis = $resume->analysisResults->first();
                return $latestAnalysis ? $latestAnalysis->overall_score : 0;
            })
            ->max() ?? 0;
    }

    private function getImprovementTrend(User $user, int $timeRange): string
    {
        $recentResumes = $user->resumes()
            ->whereHas('analysisResults')
            ->with(['analysisResults' => function ($query) {
                $query->latest();
            }])
            ->where('created_at', '>=', now()->subDays($timeRange))
            ->orderBy('created_at')
            ->get();

        if ($recentResumes->count() < 2) {
            return 'neutral';
        }

        $scores = $recentResumes->map(function ($resume) {
            $latestAnalysis = $resume->analysisResults->first();
            return $latestAnalysis ? $latestAnalysis->overall_score : 0;
        });

        $firstHalf = $scores->take(ceil($scores->count() / 2))->avg();
        $secondHalf = $scores->skip(ceil($scores->count() / 2))->avg();

        if ($secondHalf > $firstHalf + 5) {
            return 'improving';
        } elseif ($firstHalf > $secondHalf + 5) {
            return 'declining';
        }

        return 'stable';
    }

    public function getQuickStats(User $user): array
    {
        return [
            'total_resumes' => $user->resumes()->count(),
            'can_upload' => $user->canUploadResume(),
            'remaining_resumes' => $user->getRemainingResumes(),
            'current_plan' => $user->getCurrentPlan(),
            'processing_count' => $user->resumes()->where('analysis_status', 'processing')->count(),
        ];
    }
}