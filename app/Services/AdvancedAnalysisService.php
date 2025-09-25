<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\AnalysisResult;
use App\Services\AnthropicService;
use Illuminate\Support\Facades\Log;

class AdvancedAnalysisService
{
    public function __construct(
        private AnthropicService $anthropicService
    ) {}

    public function performJobMatchAnalysis(Resume $resume, array $jobData): array
    {
        try {
            $resumeText = $this->getResumeText($resume);
            
            if (!$resumeText) {
                throw new \Exception('Resume text not available for analysis');
            }

            $jobDescription = $jobData['description'] ?? '';
            $jobTitle = $jobData['title'] ?? '';
            $jobRequirements = $jobData['requirements'] ?? '';

            // Combine job information
            $fullJobDescription = trim("Job Title: {$jobTitle}\n\nJob Description:\n{$jobDescription}\n\nRequirements:\n{$jobRequirements}");

            // Perform job matching analysis
            $matchResult = $this->anthropicService->optimizeResumeForJob($resumeText, $fullJobDescription);

            // Enhanced analysis with additional metrics
            $enhancedResult = $this->enhanceJobMatchResult($matchResult, $resumeText, $jobData);

            return [
                'success' => true,
                'job_match' => $enhancedResult,
                'recommendations' => $this->generateJobMatchRecommendations($enhancedResult),
            ];

        } catch (\Exception $e) {
            Log::error('Job match analysis failed', [
                'resume_id' => $resume->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function performIndustryAnalysis(Resume $resume, string $targetIndustry): array
    {
        try {
            $resumeText = $this->getResumeText($resume);
            
            if (!$resumeText) {
                throw new \Exception('Resume text not available for analysis');
            }

            $prompt = $this->buildIndustryAnalysisPrompt($resumeText, $targetIndustry);

            $response = $this->anthropicService->analyzeResume($resumeText, [
                'depth' => 'comprehensive',
                'target_industry' => $targetIndustry,
                'focus' => 'industry_alignment',
            ]);

            return [
                'success' => true,
                'industry_alignment' => $this->calculateIndustryAlignment($response, $targetIndustry),
                'recommendations' => $this->generateIndustryRecommendations($response, $targetIndustry),
                'skills_gap' => $this->analyzeSkillsGap($resumeText, $targetIndustry),
            ];

        } catch (\Exception $e) {
            Log::error('Industry analysis failed', [
                'resume_id' => $resume->id,
                'target_industry' => $targetIndustry,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function performCareerProgressionAnalysis(Resume $resume): array
    {
        try {
            $resumeText = $this->getResumeText($resume);
            
            if (!$resumeText) {
                throw new \Exception('Resume text not available for analysis');
            }

            $prompt = $this->buildCareerProgressionPrompt($resumeText);

            // Use Anthropic to analyze career progression
            $response = $this->callAnthropicForCareerAnalysis($prompt);

            return [
                'success' => true,
                'career_trajectory' => $this->extractCareerTrajectory($response),
                'growth_potential' => $this->assessGrowthPotential($response),
                'recommendations' => $this->generateCareerRecommendations($response),
                'next_roles' => $this->suggestNextRoles($response),
            ];

        } catch (\Exception $e) {
            Log::error('Career progression analysis failed', [
                'resume_id' => $resume->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function performSalaryAnalysis(Resume $resume, ?string $targetRole = null, ?string $location = null): array
    {
        try {
            $resumeText = $this->getResumeText($resume);
            
            if (!$resumeText) {
                throw new \Exception('Resume text not available for analysis');
            }

            $prompt = $this->buildSalaryAnalysisPrompt($resumeText, $targetRole, $location);

            $response = $this->callAnthropicForSalaryAnalysis($prompt);

            return [
                'success' => true,
                'salary_range' => $this->extractSalaryRange($response),
                'factors' => $this->extractSalaryFactors($response),
                'recommendations' => $this->generateSalaryRecommendations($response),
                'market_position' => $this->assessMarketPosition($response),
            ];

        } catch (\Exception $e) {
            Log::error('Salary analysis failed', [
                'resume_id' => $resume->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getResumeText(Resume $resume): ?string
    {
        $parsedData = $resume->metadata['parsed_data'] ?? null;
        return $parsedData['raw_text'] ?? null;
    }

    private function enhanceJobMatchResult(array $matchResult, string $resumeText, array $jobData): array
    {
        $compatibilityScore = $matchResult['compatibility_score'] ?? 0;
        $matchingSkills = $matchResult['matching_skills'] ?? [];
        $missingSkills = $matchResult['missing_skills'] ?? [];

        // Calculate additional metrics
        $experienceMatch = $this->calculateExperienceMatch($resumeText, $jobData);
        $educationMatch = $this->calculateEducationMatch($resumeText, $jobData);
        $keywordDensity = $this->calculateKeywordDensity($resumeText, $jobData);

        return [
            'compatibility_score' => $compatibilityScore,
            'matching_skills' => $matchingSkills,
            'missing_skills' => $missingSkills,
            'experience_match' => $experienceMatch,
            'education_match' => $educationMatch,
            'keyword_density' => $keywordDensity,
            'recommended_additions' => $matchResult['recommended_additions'] ?? [],
            'optimization_priority' => $matchResult['optimization_priority'] ?? [],
        ];
    }

    private function calculateExperienceMatch(string $resumeText, array $jobData): array
    {
        // Extract years of experience from resume
        $resumeYears = $this->extractYearsOfExperience($resumeText);
        
        // Extract required experience from job
        $requiredYears = $this->extractRequiredExperience($jobData);

        $match = $requiredYears > 0 ? min(100, ($resumeYears / $requiredYears) * 100) : 100;

        return [
            'score' => (int) $match,
            'resume_years' => $resumeYears,
            'required_years' => $requiredYears,
            'meets_requirement' => $resumeYears >= $requiredYears,
        ];
    }

    private function calculateEducationMatch(string $resumeText, array $jobData): array
    {
        // Basic education matching logic
        $resumeEducation = $this->extractEducationLevel($resumeText);
        $requiredEducation = $this->extractRequiredEducation($jobData);

        $educationLevels = ['high_school' => 1, 'associate' => 2, 'bachelor' => 3, 'master' => 4, 'phd' => 5];
        
        $resumeLevel = $educationLevels[$resumeEducation] ?? 0;
        $requiredLevel = $educationLevels[$requiredEducation] ?? 0;

        $match = $requiredLevel > 0 ? min(100, ($resumeLevel / $requiredLevel) * 100) : 100;

        return [
            'score' => (int) $match,
            'resume_education' => $resumeEducation,
            'required_education' => $requiredEducation,
            'meets_requirement' => $resumeLevel >= $requiredLevel,
        ];
    }

    private function calculateKeywordDensity(string $resumeText, array $jobData): array
    {
        $jobText = ($jobData['description'] ?? '') . ' ' . ($jobData['requirements'] ?? '');
        $keywords = $this->extractKeywords($jobText);
        
        $totalKeywords = count($keywords);
        $foundKeywords = 0;

        foreach ($keywords as $keyword) {
            if (stripos($resumeText, $keyword) !== false) {
                $foundKeywords++;
            }
        }

        $density = $totalKeywords > 0 ? ($foundKeywords / $totalKeywords) * 100 : 0;

        return [
            'score' => (int) $density,
            'total_keywords' => $totalKeywords,
            'found_keywords' => $foundKeywords,
            'missing_keywords' => array_filter($keywords, function($keyword) use ($resumeText) {
                return stripos($resumeText, $keyword) === false;
            }),
        ];
    }

    private function extractYearsOfExperience(string $text): int
    {
        // Look for patterns like "5 years of experience", "3+ years", etc.
        if (preg_match('/(\d+)\+?\s*years?\s*(?:of\s*)?experience/i', $text, $matches)) {
            return (int) $matches[1];
        }

        // Fallback: count date ranges
        $years = 0;
        if (preg_match_all('/\b(19|20)\d{2}\b/', $text, $matches)) {
            $dates = array_map('intval', $matches[0]);
            if (count($dates) >= 2) {
                $years = max($dates) - min($dates);
            }
        }

        return max(0, $years);
    }

    private function extractRequiredExperience(array $jobData): int
    {
        $text = ($jobData['description'] ?? '') . ' ' . ($jobData['requirements'] ?? '');
        
        if (preg_match('/(\d+)\+?\s*years?\s*(?:of\s*)?experience/i', $text, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function extractEducationLevel(string $text): string
    {
        $text = strtolower($text);
        
        if (strpos($text, 'phd') !== false || strpos($text, 'doctorate') !== false) {
            return 'phd';
        }
        if (strpos($text, 'master') !== false || strpos($text, 'mba') !== false) {
            return 'master';
        }
        if (strpos($text, 'bachelor') !== false || strpos($text, 'b.s.') !== false || strpos($text, 'b.a.') !== false) {
            return 'bachelor';
        }
        if (strpos($text, 'associate') !== false) {
            return 'associate';
        }
        
        return 'high_school';
    }

    private function extractRequiredEducation(array $jobData): string
    {
        $text = strtolower(($jobData['description'] ?? '') . ' ' . ($jobData['requirements'] ?? ''));
        
        if (strpos($text, 'phd') !== false || strpos($text, 'doctorate') !== false) {
            return 'phd';
        }
        if (strpos($text, 'master') !== false || strpos($text, 'mba') !== false) {
            return 'master';
        }
        if (strpos($text, 'bachelor') !== false) {
            return 'bachelor';
        }
        if (strpos($text, 'associate') !== false) {
            return 'associate';
        }
        
        return 'high_school';
    }

    private function extractKeywords(string $text): array
    {
        // Extract important keywords from job description
        $commonWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'a', 'an'];
        
        $words = str_word_count(strtolower($text), 1);
        $words = array_filter($words, function($word) use ($commonWords) {
            return strlen($word) > 3 && !in_array($word, $commonWords);
        });

        // Count word frequency and return top keywords
        $wordCounts = array_count_values($words);
        arsort($wordCounts);
        
        return array_keys(array_slice($wordCounts, 0, 20));
    }

    private function generateJobMatchRecommendations(array $matchResult): array
    {
        $recommendations = [];

        if ($matchResult['compatibility_score'] < 70) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Low Compatibility Score',
                'description' => 'Your resume has a low compatibility score with this job. Consider significant revisions.',
                'action' => 'Major resume overhaul recommended',
            ];
        }

        if (!empty($matchResult['missing_skills'])) {
            $skillCount = count($matchResult['missing_skills']);
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Missing Key Skills',
                'description' => "You're missing {$skillCount} important skills for this role.",
                'action' => 'Add these skills to your resume if you have them, or consider skill development',
                'skills' => array_slice($matchResult['missing_skills'], 0, 5),
            ];
        }

        if ($matchResult['keyword_density']['score'] < 50) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Low Keyword Density',
                'description' => 'Your resume contains few keywords from the job description.',
                'action' => 'Incorporate more relevant keywords naturally throughout your resume',
            ];
        }

        return $recommendations;
    }

    private function buildIndustryAnalysisPrompt(string $resumeText, string $targetIndustry): string
    {
        return "Analyze this resume for alignment with the {$targetIndustry} industry:\n\n{$resumeText}\n\nProvide insights on industry fit, relevant skills, and recommendations.";
    }

    private function buildCareerProgressionPrompt(string $resumeText): string
    {
        return "Analyze the career progression shown in this resume:\n\n{$resumeText}\n\nProvide insights on career trajectory, growth potential, and suggested next steps.";
    }

    private function buildSalaryAnalysisPrompt(string $resumeText, ?string $targetRole, ?string $location): string
    {
        $prompt = "Analyze this resume for salary potential:\n\n{$resumeText}";
        
        if ($targetRole) {
            $prompt .= "\n\nTarget Role: {$targetRole}";
        }
        
        if ($location) {
            $prompt .= "\nLocation: {$location}";
        }
        
        $prompt .= "\n\nProvide salary range estimates and factors affecting compensation.";
        
        return $prompt;
    }

    // Placeholder methods for additional AI analysis calls
    private function callAnthropicForCareerAnalysis(string $prompt): array
    {
        // This would make actual Anthropic API calls for career analysis
        return ['analysis' => 'Career progression analysis result'];
    }

    private function callAnthropicForSalaryAnalysis(string $prompt): array
    {
        // This would make actual Anthropic API calls for salary analysis
        return ['analysis' => 'Salary analysis result'];
    }

    // Placeholder extraction methods
    private function calculateIndustryAlignment(array $response, string $industry): array
    {
        return ['score' => 75, 'details' => 'Industry alignment details'];
    }

    private function generateIndustryRecommendations(array $response, string $industry): array
    {
        return ['Recommendation 1', 'Recommendation 2'];
    }

    private function analyzeSkillsGap(string $resumeText, string $industry): array
    {
        return ['missing' => [], 'recommended' => []];
    }

    private function extractCareerTrajectory(array $response): array
    {
        return ['trajectory' => 'upward', 'details' => 'Career trajectory details'];
    }

    private function assessGrowthPotential(array $response): array
    {
        return ['potential' => 'high', 'reasons' => []];
    }

    private function generateCareerRecommendations(array $response): array
    {
        return ['Career recommendation 1', 'Career recommendation 2'];
    }

    private function suggestNextRoles(array $response): array
    {
        return ['Senior Developer', 'Team Lead', 'Technical Manager'];
    }

    private function extractSalaryRange(array $response): array
    {
        return ['min' => 80000, 'max' => 120000, 'median' => 100000];
    }

    private function extractSalaryFactors(array $response): array
    {
        return ['Experience level', 'Skills match', 'Industry demand'];
    }

    private function generateSalaryRecommendations(array $response): array
    {
        return ['Salary recommendation 1', 'Salary recommendation 2'];
    }

    private function assessMarketPosition(array $response): array
    {
        return ['position' => 'competitive', 'percentile' => 75];
    }
}