<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ATSSuggestion;
use App\Services\ResumeSectionParserService;
use App\Services\RealTimeAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InteractiveEditorController extends Controller
{
    private ResumeSectionParserService $sectionParser;
    private RealTimeAnalysisService $analysisService;

    public function __construct(
        ResumeSectionParserService $sectionParser,
        RealTimeAnalysisService $analysisService
    ) {
        $this->sectionParser = $sectionParser;
        $this->analysisService = $analysisService;
    }

    /**
     * Show the interactive editor page
     */
    public function show(Resume $resume): Response
    {
        // Ensure user owns this resume
        abort_unless($resume->user_id === Auth::id(), 403);

        // Get or create resume sections
        $sections = $resume->sections()->orderBy('order_index')->get();
        
        // If no sections exist, parse the resume content
        if ($sections->isEmpty()) {
            $this->parseAndCreateSections($resume);
            $sections = $resume->sections()->orderBy('order_index')->get();
        }

        // Get current suggestions
        $suggestions = ATSSuggestion::where('resume_id', $resume->id)
            ->where('status', 'pending')
            ->with('section')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate initial live scores
        $initialScores = $this->calculateLiveScores($resume, $sections);

        return Inertia::render('Resumes/InteractiveEditor', [
            'resume' => $resume->load('user'),
            'sections' => $sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'resume_id' => $section->resume_id,
                    'section_type' => $section->section_type,
                    'title' => $section->title,
                    'content' => $section->content,
                    'ats_score' => $section->ats_score,
                    'order_index' => $section->order_index,
                    'created_at' => $section->created_at->toISOString(),
                    'updated_at' => $section->updated_at->toISOString(),
                    'pending_suggestions_count' => $section->suggestions()->where('status', 'pending')->count(),
                    'has_critical_issues' => $section->suggestions()->where('priority', 'critical')->where('status', 'pending')->exists(),
                ];
            }),
            'suggestions' => $suggestions->map(function ($suggestion) {
                return [
                    'id' => $suggestion->id,
                    'resume_id' => $suggestion->resume_id,
                    'section_id' => $suggestion->section_id,
                    'suggestion_type' => $suggestion->suggestion_type,
                    'priority' => $suggestion->priority,
                    'title' => $suggestion->title,
                    'description' => $suggestion->description,
                    'original_text' => $suggestion->original_text,
                    'suggested_text' => $suggestion->suggested_text,
                    'ats_impact' => $suggestion->ats_impact,
                    'reason' => $suggestion->reason,
                    'status' => $suggestion->status,
                    'applied_at' => $suggestion->applied_at?->toISOString(),
                    'created_at' => $suggestion->created_at->toISOString(),
                    'updated_at' => $suggestion->updated_at->toISOString(),
                    'section' => $suggestion->section ? [
                        'id' => $suggestion->section->id,
                        'section_type' => $suggestion->section->section_type,
                        'title' => $suggestion->section->title,
                    ] : null,
                ];
            }),
            'initialScores' => $initialScores,
        ]);
    }

    /**
     * Parse resume content into sections
     */
    public function parseSections(Resume $resume, Request $request)
    {
        // Ensure user owns this resume
        abort_unless($resume->user_id === Auth::id(), 403);

        $request->validate([
            'force_reparse' => 'boolean',
        ]);

        // Delete existing sections if force reparse
        if ($request->get('force_reparse', false)) {
            $resume->sections()->delete();
        }

        // Parse and create sections
        $sections = $this->parseAndCreateSections($resume);

        return response()->json([
            'success' => true,
            'message' => 'Resume sections parsed successfully',
            'sections' => $sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'section_type' => $section->section_type,
                    'title' => $section->title,
                    'content' => $section->content,
                    'ats_score' => $section->ats_score,
                    'order_index' => $section->order_index,
                ];
            }),
        ]);
    }

    /**
     * Parse resume content and create sections
     */
    private function parseAndCreateSections(Resume $resume)
    {
        // Use the correct method from ResumeSectionParserService
        $sections = $this->sectionParser->parseResumeIntoSections($resume, $resume->content);
        
        // Calculate initial ATS scores for each section
        foreach ($sections as $section) {
            try {
                $analysisResult = $this->analysisService->analyzeSectionForATS(
                    $section->section_type,
                    $section->content
                );
                
                $section->update(['ats_score' => $analysisResult['section_score'] ?? 0]);
            } catch (\Exception $e) {
                // Log error but don't fail the whole process
                logger()->error('Failed to analyze section during parsing', [
                    'resume_id' => $resume->id,
                    'section_type' => $section->section_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sections;
    }

    /**
     * Calculate initial live scores
     */
    private function calculateLiveScores(Resume $resume, $sections): array
    {
        $totalScore = 0;
        $sectionCount = $sections->count();

        if ($sectionCount > 0) {
            $totalScore = $sections->sum('ats_score') / $sectionCount;
        }

        // Calculate component scores (simplified for initial implementation)
        $baseScore = max(0, min(100, $totalScore));
        
        return [
            'overall' => $baseScore,
            'ats_compatibility' => min(100, $baseScore + 5), // Slightly higher for demo
            'keyword_density' => max(0, $baseScore - 10),
            'format_score' => min(100, $baseScore + 3),
            'content_quality' => $baseScore,
            'improvement_potential' => max(0, 100 - $baseScore),
        ];
    }
}