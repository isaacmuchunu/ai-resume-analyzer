<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\AnalysisResult;
use App\Services\AnthropicService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class AdvancedAnalysisService extends AnthropicService
{
    private array $industryKeywords;
    private array $skillCategories;
    private array $atsKeywords;

    public function __construct()
    {
        parent::__construct();
        $this->loadAnalysisData();
    }

    /**
     * Comprehensive resume analysis with multiple AI models
     */
    public function performAdvancedAnalysis(Resume $resume, array $options = []): array
    {
        try {
            $content = $resume->parsed_content;
            if (empty($content)) {
                throw new Exception('Resume content is empty or could not be parsed');
            }

            // Run multiple analysis types in parallel
            $analyses = [
                'comprehensive' => $this->comprehensiveAnalysis($content, $options),
                'ats_optimization' => $this->atsOptimizationAnalysis($content, $options),
                'industry_specific' => $this->industrySpecificAnalysis($content, $options),
                'skills_analysis' => $this->advancedSkillsAnalysis($content, $options),
                'sentiment_analysis' => $this->sentimentAnalysis($content),
                'readability_analysis' => $this->readabilityAnalysis($content),
            ];

            // Combine results
            $combinedResult = $this->combineAnalysisResults($analyses);

            // Save to database
            $analysisResult = $this->saveAnalysisResult($resume, $combinedResult);

            return [
                'success' => true,
                'analysis' => $analysisResult,
                'detailed_analyses' => $analyses,
                'recommendations' => $this->generateAdvancedRecommendations($combinedResult),
                'optimization_score' => $this->calculateOptimizationScore($combinedResult),
            ];

        } catch (Exception $e) {
            Log::error('Advanced analysis failed', [
                'resume_id' => $resume->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Job-specific resume optimization
     */
    public function optimizeForJobDescription(Resume $resume, string $jobDescription, array $options = []): array
    {
        try {
            $resumeContent = $resume->parsed_content;

            $prompt = $this->buildJobOptimizationPrompt($resumeContent, $jobDescription, $options);
            $response = $this->callClaude($prompt);

            $optimization = json_decode($response, true);

            return [
                'success' => true,
                'job_match_score' => $optimization['job_match_score'] ?? 0,
                'missing_keywords' => $optimization['missing_keywords'] ?? [],
                'keyword_suggestions' => $optimization['keyword_suggestions'] ?? [],
                'section_recommendations' => $optimization['section_recommendations'] ?? [],
                'experience_gaps' => $optimization['experience_gaps'] ?? [],
                'skills_gaps' => $optimization['skills_gaps'] ?? [],
                'optimized_summary' => $optimization['optimized_summary'] ?? '',
                'tailored_achievements' => $optimization['tailored_achievements'] ?? [],
                'cover_letter_points' => $optimization['cover_letter_points'] ?? [],
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate multiple resume versions for different roles
     */
    public function generateRoleVariants(Resume $resume, array $targetRoles): array
    {
        try {
            $baseContent = $resume->parsed_content;
            $variants = [];

            foreach ($targetRoles as $role) {
                $prompt = $this->buildRoleVariantPrompt($baseContent, $role);
                $response = $this->callClaude($prompt);

                $variant = json_decode($response, true);
                $variants[$role] = [
                    'optimized_content' => $variant['optimized_content'] ?? $baseContent,
                    'key_changes' => $variant['key_changes'] ?? [],
                    'focus_areas' => $variant['focus_areas'] ?? [],
                    'removed_sections' => $variant['removed_sections'] ?? [],
                    'enhanced_sections' => $variant['enhanced_sections'] ?? [],
                ];
            }

            return [
                'success' => true,
                'variants' => $variants,
                'base_resume_id' => $resume->id,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Predict salary range based on resume content
     */
    public function predictSalaryRange(Resume $resume, array $options = []): array
    {
        try {
            $content = $resume->parsed_content;
            $location = $options['location'] ?? 'United States';
            $currency = $options['currency'] ?? 'USD';

            $prompt = $this->buildSalaryPredictionPrompt($content, $location, $currency);
            $response = $this->callClaude($prompt);

            $prediction = json_decode($response, true);

            return [
                'success' => true,
                'salary_range' => [
                    'min' => $prediction['min_salary'] ?? 0,
                    'max' => $prediction['max_salary'] ?? 0,
                    'median' => $prediction['median_salary'] ?? 0,
                    'currency' => $currency,
                ],
                'factors' => $prediction['factors'] ?? [],
                'experience_level' => $prediction['experience_level'] ?? 'mid',
                'industry' => $prediction['industry'] ?? 'general',
                'location_factor' => $prediction['location_factor'] ?? 1.0,
                'confidence' => $prediction['confidence'] ?? 0.5,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Interview preparation based on resume
     */
    public function generateInterviewPrep(Resume $resume, string $jobType = 'general'): array
    {
        try {
            $content = $resume->parsed_content;

            $prompt = $this->buildInterviewPrepPrompt($content, $jobType);
            $response = $this->callClaude($prompt);

            $prep = json_decode($response, true);

            return [
                'success' => true,
                'likely_questions' => $prep['likely_questions'] ?? [],
                'behavioral_questions' => $prep['behavioral_questions'] ?? [],
                'technical_questions' => $prep['technical_questions'] ?? [],
                'suggested_answers' => $prep['suggested_answers'] ?? [],
                'stories_to_prepare' => $prep['stories_to_prepare'] ?? [],
                'weakness_areas' => $prep['weakness_areas'] ?? [],
                'strength_areas' => $prep['strength_areas'] ?? [],
                'questions_to_ask' => $prep['questions_to_ask'] ?? [],
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // Private helper methods

    private function comprehensiveAnalysis(string $content, array $options): array
    {
        $cacheKey = "comprehensive_analysis_" . md5($content);

        return Cache::remember($cacheKey, 3600, function () use ($content, $options) {
            $prompt = $this->buildComprehensivePrompt($content, $options);
            $response = $this->callClaude($prompt);
            return json_decode($response, true);
        });
    }

    private function atsOptimizationAnalysis(string $content, array $options): array
    {
        $prompt = $this->buildATSPrompt($content);
        $response = $this->callClaude($prompt);
        return json_decode($response, true);
    }

    private function industrySpecificAnalysis(string $content, array $options): array
    {
        $industry = $options['industry'] ?? $this->detectIndustry($content);
        $prompt = $this->buildIndustryPrompt($content, $industry);
        $response = $this->callClaude($prompt);
        return json_decode($response, true);
    }

    private function advancedSkillsAnalysis(string $content, array $options): array
    {
        $prompt = $this->buildSkillsAnalysisPrompt($content);
        $response = $this->callClaude($prompt);
        return json_decode($response, true);
    }

    private function sentimentAnalysis(string $content): array
    {
        $prompt = "Analyze the sentiment and tone of this resume content. Return JSON with sentiment_score (-1 to 1), tone_characteristics, confidence_indicators, and improvement_suggestions:\n\n" . $content;
        $response = $this->callClaude($prompt);
        return json_decode($response, true);
    }

    private function readabilityAnalysis(string $content): array
    {
        // Calculate various readability metrics
        $wordCount = str_word_count($content);
        $sentenceCount = preg_match_all('/[.!?]+/', $content);
        $avgWordsPerSentence = $sentenceCount > 0 ? $wordCount / $sentenceCount : 0;

        // Flesch Reading Ease approximation
        $avgSyllables = $this->estimateSyllables($content);
        $fleschScore = 206.835 - (1.015 * $avgWordsPerSentence) - (84.6 * ($avgSyllables / $wordCount));

        return [
            'flesch_score' => max(0, min(100, $fleschScore)),
            'avg_words_per_sentence' => $avgWordsPerSentence,
            'word_count' => $wordCount,
            'sentence_count' => $sentenceCount,
            'readability_level' => $this->getReadabilityLevel($fleschScore),
            'complexity_indicators' => $this->analyzeComplexity($content),
        ];
    }

    private function buildComprehensivePrompt(string $content, array $options): string
    {
        return "Perform a comprehensive analysis of this resume. Return detailed JSON with scores (0-100), recommendations, extracted information, and improvement suggestions. Include sections for: overall_score, content_quality, formatting_assessment, keyword_optimization, experience_evaluation, education_assessment, skills_categorization, achievements_analysis, and specific_recommendations.\n\nResume Content:\n" . $content;
    }

    private function buildJobOptimizationPrompt(string $resumeContent, string $jobDescription, array $options): string
    {
        return "Analyze this resume against the job description and provide optimization recommendations. Return JSON with job_match_score, missing_keywords, keyword_suggestions, section_recommendations, experience_gaps, skills_gaps, optimized_summary, tailored_achievements, and cover_letter_points.\n\nResume:\n{$resumeContent}\n\nJob Description:\n{$jobDescription}";
    }

    private function buildRoleVariantPrompt(string $content, string $role): string
    {
        return "Adapt this resume content for a {$role} position. Return JSON with optimized_content, key_changes made, focus_areas, removed_sections, and enhanced_sections.\n\nOriginal Resume:\n{$content}";
    }

    private function buildSalaryPredictionPrompt(string $content, string $location, string $currency): string
    {
        return "Based on this resume content, predict salary range for {$location} in {$currency}. Consider experience level, skills, industry, and location. Return JSON with min_salary, max_salary, median_salary, factors influencing salary, experience_level, industry, location_factor, and confidence score.\n\nResume:\n{$content}";
    }

    private function buildInterviewPrepPrompt(string $content, string $jobType): string
    {
        return "Generate interview preparation based on this resume for {$jobType} positions. Return JSON with likely_questions, behavioral_questions, technical_questions, suggested_answers, stories_to_prepare, weakness_areas, strength_areas, and questions_to_ask.\n\nResume:\n{$content}";
    }

    private function buildATSPrompt(string $content): string
    {
        return "Analyze this resume for ATS (Applicant Tracking System) optimization. Return JSON with ats_score, formatting_issues, keyword_density, section_structure, compatibility_assessment, and specific_improvements.\n\nResume:\n" . $content;
    }

    private function buildIndustryPrompt(string $content, string $industry): string
    {
        return "Analyze this resume specifically for the {$industry} industry. Return JSON with industry_alignment_score, relevant_experience, industry_keywords, certification_recommendations, and industry_specific_suggestions.\n\nResume:\n" . $content;
    }

    private function buildSkillsAnalysisPrompt(string $content): string
    {
        return "Perform advanced skills analysis on this resume. Categorize skills into technical, soft, industry-specific, and emerging skills. Return JSON with skill_categories, proficiency_levels, skill_gaps, trending_skills_missing, and skill_development_path.\n\nResume:\n" . $content;
    }

    private function combineAnalysisResults(array $analyses): array
    {
        $combined = [
            'overall_score' => 0,
            'detailed_scores' => [],
            'recommendations' => [],
            'extracted_data' => [],
            'analysis_metadata' => [
                'analysis_date' => now()->toISOString(),
                'analysis_version' => '2.0',
                'models_used' => ['claude-3.5-sonnet'],
            ],
        ];

        // Combine scores with weights
        $weights = [
            'comprehensive' => 0.4,
            'ats_optimization' => 0.2,
            'industry_specific' => 0.2,
            'skills_analysis' => 0.1,
            'sentiment_analysis' => 0.05,
            'readability_analysis' => 0.05,
        ];

        $totalScore = 0;
        foreach ($analyses as $type => $analysis) {
            if (isset($analysis['overall_score']) && isset($weights[$type])) {
                $totalScore += $analysis['overall_score'] * $weights[$type];
            }

            // Combine recommendations
            if (isset($analysis['recommendations'])) {
                $combined['recommendations'] = array_merge(
                    $combined['recommendations'],
                    $analysis['recommendations']
                );
            }
        }

        $combined['overall_score'] = min(100, max(0, round($totalScore)));
        $combined['detailed_scores'] = $analyses;

        return $combined;
    }

    private function saveAnalysisResult(Resume $resume, array $combinedResult): AnalysisResult
    {
        return AnalysisResult::create([
            'resume_id' => $resume->id,
            'analysis_type' => 'advanced_comprehensive',
            'overall_score' => $combinedResult['overall_score'],
            'ats_score' => $combinedResult['detailed_scores']['ats_optimization']['ats_score'] ?? null,
            'content_score' => $combinedResult['detailed_scores']['comprehensive']['content_score'] ?? null,
            'format_score' => $combinedResult['detailed_scores']['comprehensive']['format_score'] ?? null,
            'keyword_score' => $combinedResult['detailed_scores']['comprehensive']['keyword_score'] ?? null,
            'detailed_scores' => $combinedResult['detailed_scores'],
            'recommendations' => array_unique($combinedResult['recommendations']),
            'ai_insights' => json_encode($combinedResult['analysis_metadata']),
        ]);
    }

    private function generateAdvancedRecommendations(array $combinedResult): array
    {
        $recommendations = [];

        // Priority-based recommendations
        if ($combinedResult['overall_score'] < 70) {
            $recommendations[] = 'Your resume needs significant improvement to be competitive.';
        }

        // Add more sophisticated recommendation logic here

        return array_unique($recommendations);
    }

    private function calculateOptimizationScore(array $combinedResult): int
    {
        // Calculate how much the resume can be improved
        $currentScore = $combinedResult['overall_score'];
        $potentialScore = min(100, $currentScore + 30); // Assume 30% improvement potential

        return $potentialScore - $currentScore;
    }

    private function detectIndustry(string $content): string
    {
        // Simple industry detection based on keywords
        $industries = [
            'technology' => ['software', 'programming', 'developer', 'engineer', 'tech'],
            'healthcare' => ['medical', 'nurse', 'doctor', 'healthcare', 'clinical'],
            'finance' => ['finance', 'banking', 'accounting', 'investment', 'financial'],
            'marketing' => ['marketing', 'advertising', 'brand', 'digital', 'campaign'],
        ];

        foreach ($industries as $industry => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    return $industry;
                }
            }
        }

        return 'general';
    }

    private function loadAnalysisData(): void
    {
        // Load industry keywords, skill categories, etc.
        $this->industryKeywords = [
            'technology' => ['software', 'programming', 'development', 'engineering'],
            // Add more industries
        ];

        $this->skillCategories = [
            'technical' => ['programming', 'software', 'database', 'cloud'],
            'soft' => ['communication', 'leadership', 'teamwork', 'problem-solving'],
            // Add more categories
        ];
    }

    private function estimateSyllables(string $text): int
    {
        // Simple syllable estimation
        $words = str_word_count(strtolower($text), 1);
        $totalSyllables = 0;

        foreach ($words as $word) {
            $syllables = preg_match_all('/[aeiouy]+/', $word);
            $totalSyllables += max(1, $syllables);
        }

        return $totalSyllables;
    }

    private function getReadabilityLevel(float $fleschScore): string
    {
        if ($fleschScore >= 90) return 'Very Easy';
        if ($fleschScore >= 80) return 'Easy';
        if ($fleschScore >= 70) return 'Fairly Easy';
        if ($fleschScore >= 60) return 'Standard';
        if ($fleschScore >= 50) return 'Fairly Difficult';
        if ($fleschScore >= 30) return 'Difficult';
        return 'Very Difficult';
    }

    private function analyzeComplexity(string $content): array
    {
        return [
            'long_sentences' => preg_match_all('/[.!?][^.!?]{100,}/', $content),
            'complex_words' => preg_match_all('/\b\w{10,}\b/', $content),
            'passive_voice_indicators' => preg_match_all('/\b(was|were|been|being)\s+\w+ed\b/', $content),
        ];
    }
}