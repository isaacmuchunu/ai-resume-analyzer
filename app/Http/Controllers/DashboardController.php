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

        // Get current tenant or use default for demo
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : $this->getDefaultTenant($request);

        // Get dashboard statistics
        $stats = [
            'total_resumes' => $user->resumes()->count(),
            'analyzed_resumes' => $user->resumes()->where('analysis_status', 'completed')->count(),
            'average_score' => $this->getAverageScore($user),
            'recent_uploads' => $user->resumes()->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        // Get recent resumes with their latest analysis
        $recent_resumes = $user->resumes()
            ->with('analysisResults')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($resume) {
                return [
                    'id' => $resume->id,
                    'original_filename' => $resume->original_filename,
                    'analysis_status' => $resume->analysis_status,
                    'created_at' => $resume->created_at,
                    'latest_analysis' => $resume->latestAnalysis() ? [
                        'overall_score' => $resume->latestAnalysis()->overall_score,
                    ] : null,
                ];
            });

        return Inertia::render('Dashboard', [
            'tenant' => [
                'name' => $tenant->name ?? 'Demo Tenant',
                'plan' => $tenant->plan ?? 'professional',
                'branding' => $tenant->getBrandingData ?? [],
            ],
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role,
            ],
            'stats' => $stats,
            'recent_resumes' => $recent_resumes,
        ]);
    }

    private function getDefaultTenant($request)
    {
        // For demo purposes, try to get tenant from URL parameter or use demo-corp
        $tenantId = $request->get('tenant', 'demo-corp');

        $tenant = \App\Models\Tenant::find($tenantId);

        if ($tenant) {
            $tenant->makeCurrent();
            app()->instance('currentTenant', $tenant);
            return $tenant;
        }

        // Return a fallback tenant object
        return (object) [
            'name' => 'Demo Corporation',
            'plan' => 'enterprise',
            'getBrandingData' => [],
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