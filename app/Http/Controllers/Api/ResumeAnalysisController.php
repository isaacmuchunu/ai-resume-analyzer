<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ATSSuggestion;
use App\Services\RealTimeAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ResumeAnalysisController extends Controller
{
    protected RealTimeAnalysisService $analysisService;

    public function __construct(RealTimeAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Analyze a specific section for ATS compatibility
     */
    public function analyzeSection(Request $request, Resume $resume): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'section_type' => 'required|string|in:contact,summary,experience,education,skills,projects,certifications,achievements',
            'content' => 'required|string|min:1',
            'job_description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $analysis = $this->analysisService->analyzeSectionForATS(
                $request->section_type,
                $request->content,
                $request->job_description
            );

            return response()->json([
                'success' => true,
                'data' => $analysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get keyword suggestions for content
     */
    public function getKeywordSuggestions(Request $request, Resume $resume): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:1',
            'target_role' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $suggestions = $this->analysisService->getKeywordSuggestions(
                $request->content,
                $request->target_role
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'suggestions' => $suggestions,
                    'total_count' => count($suggestions),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Keyword analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get live ATS preview for entire resume
     */
    public function getATSPreview(Request $request, Resume $resume): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resume_text' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $recommendations = $this->analysisService->getLiveRecommendations($request->resume_text);
            
            // Calculate overall ATS score
            $sections = $resume->sections()->get();
            $totalScore = 0;
            $sectionCount = 0;
            
            foreach ($sections as $section) {
                $content = is_array($section->content) ? json_encode($section->content) : $section->content;
                $score = $this->analysisService->calculateSectionATSScore($section->section_type, $content);
                $totalScore += $score;
                $sectionCount++;
            }
            
            $overallScore = $sectionCount > 0 ? round($totalScore / $sectionCount) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'overall_ats_score' => $overallScore,
                    'recommendations' => $recommendations,
                    'section_scores' => $sections->map(function ($section) {
                        $content = is_array($section->content) ? json_encode($section->content) : $section->content;
                        return [
                            'section_type' => $section->section_type,
                            'score' => $this->analysisService->calculateSectionATSScore($section->section_type, $content),
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ATS preview failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimize resume for a specific job
     */
    public function optimizeForJob(Request $request, Resume $resume): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_title' => 'required|string|max:255',
            'job_description' => 'required|string|min:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sections = $resume->sections()->get();
            $optimizations = [];
            
            foreach ($sections as $section) {
                $content = is_array($section->content) ? json_encode($section->content) : $section->content;
                $analysis = $this->analysisService->analyzeSectionForATS(
                    $section->section_type,
                    $content,
                    $request->job_description
                );
                
                $optimizations[] = [
                    'section_id' => $section->id,
                    'section_type' => $section->section_type,
                    'current_score' => $analysis['ats_score'],
                    'suggestions' => $analysis['suggestions'],
                    'missing_keywords' => array_slice($analysis['keywords'] ?? [], 0, 5),
                ];
            }

            // Store job optimization
            $jobOptimization = $resume->jobOptimizations()->create([
                'job_title' => $request->job_title,
                'job_description' => $request->job_description,
                'match_score' => $this->calculateJobMatchScore($resume, $request->job_description),
                'optimization_data' => [
                    'sections' => $optimizations,
                    'generated_at' => now()->toISOString(),
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'optimization_id' => $jobOptimization->id,
                    'match_score' => $jobOptimization->match_score,
                    'optimizations' => $optimizations,
                    'summary' => [
                        'total_suggestions' => collect($optimizations)->sum(fn($opt) => count($opt['suggestions'])),
                        'critical_issues' => collect($optimizations)->sum(fn($opt) => 
                            count(array_filter($opt['suggestions'], fn($s) => $s['priority'] === 'critical'))
                        ),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Job optimization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update section content and get real-time analysis
     */
    public function updateSectionWithAnalysis(Request $request, Resume $resume, ResumeSection $section): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|array|min:1',
            'analyze_realtime' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Update section content
            $section->update([
                'content' => $request->content,
            ]);

            $response = [
                'success' => true,
                'data' => [
                    'section' => $section->fresh(),
                ],
            ];

            // Perform real-time analysis if requested
            if ($request->boolean('analyze_realtime', true)) {
                $contentString = json_encode($request->content);
                $analysis = $this->analysisService->analyzeSectionForATS(
                    $section->section_type,
                    $contentString
                );

                // Update section ATS score
                $section->update([
                    'ats_score' => $analysis['ats_score'],
                ]);

                $response['data']['analysis'] = $analysis;
                $response['data']['section'] = $section->fresh();
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Section update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply an ATS suggestion
     */
    public function applySuggestion(Request $request, Resume $resume, ATSSuggestion $suggestion): JsonResponse
    {
        try {
            if ($suggestion->resume_id !== $resume->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion does not belong to this resume.',
                ], 403);
            }

            if (!$suggestion->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion has already been processed.',
                ], 400);
            }

            // Mark suggestion as applied
            $suggestion->markAsApplied();

            // If suggestion has a section, update the section content
            if ($suggestion->section && $suggestion->suggested_text) {
                $section = $suggestion->section;
                $currentContent = $section->content;
                
                // Apply the suggestion to the content
                // This is a simplified implementation - you might want more sophisticated text replacement
                if (is_array($currentContent) && isset($currentContent['text'])) {
                    $currentContent['text'] = str_replace(
                        $suggestion->original_text ?: '',
                        $suggestion->suggested_text,
                        $currentContent['text']
                    );
                    
                    $section->update(['content' => $currentContent]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'suggestion' => $suggestion->fresh(),
                    'section' => $suggestion->section?->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply suggestion: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dismiss an ATS suggestion
     */
    public function dismissSuggestion(Request $request, Resume $resume, ATSSuggestion $suggestion): JsonResponse
    {
        try {
            if ($suggestion->resume_id !== $resume->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion does not belong to this resume.',
                ], 403);
            }

            $suggestion->markAsDismissed();

            return response()->json([
                'success' => true,
                'data' => [
                    'suggestion' => $suggestion->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to dismiss suggestion: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate job match score
     */
    protected function calculateJobMatchScore(Resume $resume, string $jobDescription): int
    {
        $resumeText = '';
        foreach ($resume->sections as $section) {
            $content = is_array($section->content) ? json_encode($section->content) : $section->content;
            $resumeText .= ' ' . $content;
        }

        $jobKeywords = $this->extractKeywords($jobDescription);
        $resumeKeywords = $this->extractKeywords($resumeText);
        
        $matchingKeywords = array_intersect($jobKeywords, $resumeKeywords);
        $matchScore = count($jobKeywords) > 0 ? (count($matchingKeywords) / count($jobKeywords)) * 100 : 0;
        
        return min(100, max(0, round($matchScore)));
    }

    /**
     * Extract keywords from text
     */
    protected function extractKeywords(string $text): array
    {
        $words = str_word_count(strtolower($text), 1);
        $keywords = array_filter($words, fn($word) => strlen($word) > 3);
        return array_unique($keywords);
    }
}