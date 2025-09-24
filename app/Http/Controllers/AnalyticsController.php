<?php

namespace App\Http\Controllers;

use App\Models\UserAnalytics;
use App\Models\Resume;
use App\Models\AnalysisResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $timeRange = $request->get('range', '30'); // days

        // Get analytics data
        $analytics = $this->getAnalyticsData($user, $timeRange);

        return Inertia::render('Analytics', [
            'analytics' => $analytics,
            'timeRange' => $timeRange,
        ]);
    }

    public function api(Request $request)
    {
        $user = $request->user();
        $timeRange = $request->get('range', '30');

        return response()->json([
            'analytics' => $this->getAnalyticsData($user, $timeRange)
        ]);
    }

    private function getAnalyticsData($user, $timeRange)
    {
        $startDate = now()->subDays($timeRange);

        // Daily activity data
        $dailyActivity = UserAnalytics::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get()
            ->map(function ($analytics) {
                return [
                    'date' => $analytics->date->format('Y-m-d'),
                    'resumes_uploaded' => $analytics->resumes_uploaded,
                    'analyses_completed' => $analytics->analyses_completed,
                    'downloads' => $analytics->downloads_count,
                    'page_views' => $analytics->page_views,
                ];
            });

        // Resume analysis scores over time
        $scoresTrend = Resume::where('user_id', $user->id)
            ->whereHas('analysisResults')
            ->with(['analysisResults' => function ($query) {
                $query->latest();
            }])
            ->where('created_at', '>=', $startDate)
            ->get()
            ->map(function ($resume) {
                $latestAnalysis = $resume->analysisResults->first();
                return [
                    'date' => $resume->created_at->format('Y-m-d'),
                    'overall_score' => $latestAnalysis->overall_score ?? 0,
                    'ats_score' => $latestAnalysis->ats_score ?? 0,
                    'content_score' => $latestAnalysis->content_score ?? 0,
                    'format_score' => $latestAnalysis->format_score ?? 0,
                    'keyword_score' => $latestAnalysis->keyword_score ?? 0,
                ];
            });

        // Top performing resumes
        $topResumes = Resume::where('user_id', $user->id)
            ->whereHas('analysisResults')
            ->with(['analysisResults' => function ($query) {
                $query->latest();
            }])
            ->get()
            ->map(function ($resume) {
                $latestAnalysis = $resume->analysisResults->first();
                return [
                    'id' => $resume->id,
                    'filename' => $resume->original_filename,
                    'score' => $latestAnalysis->overall_score ?? 0,
                    'created_at' => $resume->created_at,
                ];
            })
            ->sortByDesc('score')
            ->take(5)
            ->values();

        // Skills analysis
        $allSkills = AnalysisResult::whereHas('resume', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->flatMap(function ($analysis) {
                $extracted = $analysis->extracted_skills ?? [];
                $missing = $analysis->missing_skills ?? [];
                return collect($extracted)->merge($missing);
            })
            ->countBy()
            ->sortDesc()
            ->take(10);

        // Summary statistics
        $totalResumes = Resume::where('user_id', $user->id)->count();
        $analyzedResumes = Resume::where('user_id', $user->id)
            ->where('analysis_status', 'completed')
            ->count();

        $averageScore = AnalysisResult::whereHas('resume', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->avg('overall_score') ?? 0;

        $recentActivity = UserAnalytics::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->sum('resumes_uploaded');

        return [
            'summary' => [
                'total_resumes' => $totalResumes,
                'analyzed_resumes' => $analyzedResumes,
                'average_score' => round($averageScore),
                'recent_uploads' => $recentActivity,
                'analysis_completion_rate' => $totalResumes > 0 ? round(($analyzedResumes / $totalResumes) * 100) : 0,
            ],
            'daily_activity' => $dailyActivity,
            'scores_trend' => $scoresTrend,
            'top_resumes' => $topResumes,
            'skills_analysis' => $allSkills->toArray(),
            'time_range' => $timeRange,
        ];
    }
}