<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Models\AnalysisResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AnalysisApiController extends Controller
{
    /**
     * Get analysis results for a resume
     */
    public function show(Request $request, Resume $resume): JsonResponse
    {
        // Ensure user can access this resume
        if ($resume->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Resume not found.',
            ], 404);
        }

        $analysisResult = $resume->analysisResult;

        if (!$analysisResult) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis not yet available for this resume.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'resume_id' => $resume->id,
                'analysis' => $analysisResult,
                'sections' => $resume->sections()->get(),
                'suggestions' => $resume->suggestions()->where('is_dismissed', false)->get(),
                'overall_score' => $analysisResult->overall_score,
                'ats_score' => $analysisResult->ats_score,
                'content_score' => $analysisResult->content_score,
                'format_score' => $analysisResult->format_score,
                'keyword_score' => $analysisResult->keyword_score,
            ],
        ]);
    }

    /**
     * Get analysis history for a resume
     */
    public function history(Request $request, Resume $resume): JsonResponse
    {
        // Ensure user can access this resume
        if ($resume->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Resume not found.',
            ], 404);
        }

        $history = $resume->versions()
            ->with('analysisResult')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($version) {
                return [
                    'version_id' => $version->id,
                    'created_at' => $version->created_at,
                    'analysis' => $version->analysisResult,
                    'changes_summary' => $version->changes_summary,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'resume_id' => $resume->id,
                'history' => $history,
                'total_versions' => $history->count(),
            ],
        ]);
    }

    /**
     * Submit feedback on analysis results
     */
    public function feedback(Request $request, Resume $resume): JsonResponse
    {
        // Ensure user can access this resume
        if ($resume->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Resume not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|between:1,5',
            'feedback_text' => 'nullable|string|max:1000',
            'helpful_suggestions' => 'nullable|array',
            'helpful_suggestions.*' => 'integer|exists:resume_suggestions,id',
            'improvement_areas' => 'nullable|array',
            'improvement_areas.*' => 'string|in:accuracy,relevance,clarity,completeness,timeliness',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Store feedback in analysis result
            $analysisResult = $resume->analysisResult;
            
            if (!$analysisResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'No analysis available to provide feedback on.',
                ], 404);
            }

            $feedbackData = [
                'user_rating' => $request->rating,
                'feedback_text' => $request->feedback_text,
                'helpful_suggestions' => $request->helpful_suggestions ?? [],
                'improvement_areas' => $request->improvement_areas ?? [],
                'submitted_at' => now()->toISOString(),
            ];

            // Update or create feedback
            $currentFeedback = $analysisResult->feedback ?? [];
            $currentFeedback[] = $feedbackData;
            
            $analysisResult->update([
                'feedback' => $currentFeedback,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback submitted successfully.',
                'data' => [
                    'feedback_id' => count($currentFeedback),
                    'submitted_at' => $feedbackData['submitted_at'],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze arbitrary text (public endpoint)
     */
    public function analyzeText(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|min:50|max:10000',
            'analysis_type' => 'required|string|in:ats,content,format,keywords',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // This is a simplified analysis for demo purposes
            $text = $request->text;
            $analysisType = $request->analysis_type;
            
            $analysis = $this->performTextAnalysis($text, $analysisType);

            return response()->json([
                'success' => true,
                'data' => [
                    'analysis_type' => $analysisType,
                    'text_length' => strlen($text),
                    'word_count' => str_word_count($text),
                    'analysis' => $analysis,
                    'analyzed_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Text analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Perform basic text analysis
     */
    private function performTextAnalysis(string $text, string $type): array
    {
        switch ($type) {
            case 'ats':
                return $this->analyzeATS($text);
            case 'content':
                return $this->analyzeContent($text);
            case 'format':
                return $this->analyzeFormat($text);
            case 'keywords':
                return $this->analyzeKeywords($text);
            default:
                return ['error' => 'Unknown analysis type'];
        }
    }

    private function analyzeATS(string $text): array
    {
        $score = min(100, max(0, strlen($text) / 20)); // Simple scoring based on length
        
        return [
            'ats_score' => round($score),
            'recommendations' => [
                'Add more relevant keywords',
                'Use standard section headers',
                'Ensure consistent formatting',
            ],
        ];
    }

    private function analyzeContent(string $text): array
    {
        $wordCount = str_word_count($text);
        $score = min(100, $wordCount / 5); // Simple scoring
        
        return [
            'content_score' => round($score),
            'word_count' => $wordCount,
            'recommendations' => [
                'Include quantifiable achievements',
                'Use action verbs',
                'Be specific about accomplishments',
            ],
        ];
    }

    private function analyzeFormat(string $text): array
    {
        $hasNumbers = preg_match('/\d/', $text);
        $hasBullets = preg_match('/[•\-\*]/', $text);
        $score = ($hasNumbers ? 50 : 0) + ($hasBullets ? 50 : 0);
        
        return [
            'format_score' => $score,
            'has_bullet_points' => $hasBullets,
            'has_numbers' => $hasNumbers,
            'recommendations' => [
                'Use bullet points for clarity',
                'Include quantifiable metrics',
                'Maintain consistent formatting',
            ],
        ];
    }

    private function analyzeKeywords(string $text): array
    {
        $words = str_word_count(strtolower($text), 1);
        $commonKeywords = ['experience', 'management', 'development', 'team', 'project'];
        $foundKeywords = array_intersect($words, $commonKeywords);
        
        return [
            'keyword_score' => count($foundKeywords) * 20,
            'found_keywords' => array_values($foundKeywords),
            'missing_keywords' => array_diff($commonKeywords, $foundKeywords),
            'recommendations' => [
                'Include industry-specific keywords',
                'Use job posting terminology',
                'Add technical skills',
            ],
        ];
    }
}