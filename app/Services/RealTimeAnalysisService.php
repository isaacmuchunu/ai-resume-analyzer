<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ATSSuggestion;
use App\Models\JobOptimization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RealTimeAnalysisService
{
    protected AnthropicService $anthropicService;
    protected array $atsKeywords;
    protected array $industryKeywords;

    public function __construct(AnthropicService $anthropicService)
    {
        $this->anthropicService = $anthropicService;
        $this->loadATSKeywords();
        $this->loadIndustryKeywords();
    }

    /**
     * Analyze a specific section for ATS compatibility
     */
    public function analyzeSectionForATS(string $sectionType, string $content, ?string $jobDescription = null): array
    {
        $cacheKey = "ats_analysis_{$sectionType}_" . md5($content . ($jobDescription ?? ''));
        
        return Cache::remember($cacheKey, 300, function () use ($sectionType, $content, $jobDescription) {
            $analysis = [
                'ats_score' => 0,
                'suggestions' => [],
                'keywords' => [],
                'improvements' => [],
                'formatting_issues' => [],
            ];

            // Base ATS analysis
            $analysis = $this->performBaseATSAnalysis($sectionType, $content, $analysis);
            
            // Job-specific analysis if job description is provided
            if ($jobDescription) {
                $analysis = $this->performJobSpecificAnalysis($sectionType, $content, $jobDescription, $analysis);
            }

            // AI-powered analysis for complex suggestions
            $analysis = $this->performAIAnalysis($sectionType, $content, $analysis);

            return $analysis;
        });
    }

    /**
     * Get keyword suggestions for content
     */
    public function getKeywordSuggestions(string $content, string $targetRole): array
    {
        $suggestions = [];
        $contentLower = strtolower($content);
        
        // Get industry-specific keywords
        $industryKeywords = $this->getIndustryKeywords($targetRole);
        
        foreach ($industryKeywords as $keyword) {
            if (!str_contains($contentLower, strtolower($keyword))) {
                $suggestions[] = [
                    'keyword' => $keyword,
                    'type' => 'missing',
                    'importance' => $this->getKeywordImportance($keyword, $targetRole),
                    'suggestion' => $this->generateKeywordSuggestion($keyword, $targetRole),
                ];
            }
        }

        return array_slice($suggestions, 0, 10); // Limit to top 10 suggestions
    }

    /**
     * Calculate ATS score for a specific section
     */
    public function calculateSectionATSScore(string $sectionType, string $content): int
    {
        $score = 0;
        $maxScore = 100;
        
        switch ($sectionType) {
            case 'contact':
                $score = $this->calculateContactScore($content);
                break;
            case 'summary':
                $score = $this->calculateSummaryScore($content);
                break;
            case 'experience':
                $score = $this->calculateExperienceScore($content);
                break;
            case 'education':
                $score = $this->calculateEducationScore($content);
                break;
            case 'skills':
                $score = $this->calculateSkillsScore($content);
                break;
            default:
                $score = $this->calculateGenericScore($content);
        }

        return min($maxScore, max(0, $score));
    }

    /**
     * Get live recommendations for resume content
     */
    public function getLiveRecommendations(string $resumeText): array
    {
        $recommendations = [];
        
        // Quick format checks
        $recommendations = array_merge($recommendations, $this->getFormattingRecommendations($resumeText));
        
        // Keyword density analysis
        $recommendations = array_merge($recommendations, $this->getKeywordDensityRecommendations($resumeText));
        
        // Structure recommendations
        $recommendations = array_merge($recommendations, $this->getStructureRecommendations($resumeText));
        
        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });

        return array_slice($recommendations, 0, 15); // Limit to top 15 recommendations
    }

    /**
     * Generate ATS suggestions for a resume section
     */
    public function generateSuggestionsForSection(ResumeSection $section): Collection
    {
        $suggestions = collect();
        $content = is_array($section->content) ? json_encode($section->content) : $section->content;
        
        $analysis = $this->analyzeSectionForATS($section->section_type, $content);
        
        foreach ($analysis['suggestions'] as $suggestion) {
            $suggestions->push(new ATSSuggestion([
                'resume_id' => $section->resume_id,
                'section_id' => $section->id,
                'suggestion_type' => $suggestion['type'],
                'priority' => $suggestion['priority'],
                'title' => $suggestion['title'],
                'description' => $suggestion['description'],
                'original_text' => $suggestion['original_text'] ?? '',
                'suggested_text' => $suggestion['suggested_text'] ?? '',
                'ats_impact' => $suggestion['ats_impact'] ?? 0,
                'reason' => $suggestion['reason'] ?? '',
            ]));
        }

        return $suggestions;
    }

    /**
     * Perform base ATS analysis
     */
    protected function performBaseATSAnalysis(string $sectionType, string $content, array $analysis): array
    {
        // Check for basic formatting issues
        if (strlen($content) < 10) {
            $analysis['suggestions'][] = [
                'type' => 'content',
                'priority' => 'high',
                'title' => 'Section too short',
                'description' => 'This section needs more detailed content to be effective.',
                'ats_impact' => 15,
                'reason' => 'ATS systems favor detailed, keyword-rich content.',
            ];
        }

        // Check for keyword presence
        $keywords = $this->extractKeywords($content);
        $analysis['keywords'] = $keywords;
        
        // Calculate base score
        $analysis['ats_score'] = $this->calculateSectionATSScore($sectionType, $content);

        return $analysis;
    }

    /**
     * Perform job-specific analysis
     */
    protected function performJobSpecificAnalysis(string $sectionType, string $content, string $jobDescription, array $analysis): array
    {
        $jobKeywords = $this->extractKeywords($jobDescription);
        $contentKeywords = $this->extractKeywords($content);
        
        $missingKeywords = array_diff($jobKeywords, $contentKeywords);
        
        foreach (array_slice($missingKeywords, 0, 5) as $keyword) {
            $analysis['suggestions'][] = [
                'type' => 'keyword',
                'priority' => 'high',
                'title' => "Add keyword: {$keyword}",
                'description' => "This keyword from the job description is missing from your {$sectionType} section.",
                'suggested_text' => $this->generateKeywordIntegration($keyword, $sectionType),
                'ats_impact' => 10,
                'reason' => 'Job-specific keywords improve ATS matching.',
            ];
        }

        return $analysis;
    }

    /**
     * Perform AI-powered analysis
     */
    protected function performAIAnalysis(string $sectionType, string $content, array $analysis): array
    {
        try {
            $prompt = $this->buildAIAnalysisPrompt($sectionType, $content);
            $aiResponse = $this->anthropicService->analyzeText($prompt);
            
            // Parse AI response and add suggestions
            $aiSuggestions = $this->parseAISuggestions($aiResponse);
            $analysis['suggestions'] = array_merge($analysis['suggestions'], $aiSuggestions);
            
        } catch (\Exception $e) {
            Log::warning('AI analysis failed for section', [
                'section_type' => $sectionType,
                'error' => $e->getMessage(),
            ]);
        }

        return $analysis;
    }

    /**
     * Calculate contact section score
     */
    protected function calculateContactScore(string $content): int
    {
        $score = 0;
        $content = strtolower($content);
        
        // Check for required elements
        if (str_contains($content, '@')) $score += 25; // Email
        if (preg_match('/\d{3}[-.]?\d{3}[-.]?\d{4}/', $content)) $score += 25; // Phone
        if (str_contains($content, 'linkedin')) $score += 20; // LinkedIn
        if (preg_match('/\b[A-Z][a-z]+,?\s+[A-Z]{2}\b/', $content)) $score += 20; // Location
        if (str_contains($content, 'github') || str_contains($content, 'portfolio')) $score += 10; // Portfolio
        
        return $score;
    }

    /**
     * Calculate summary section score
     */
    protected function calculateSummaryScore(string $content): int
    {
        $score = 0;
        $wordCount = str_word_count($content);
        
        // Optimal length (50-150 words)
        if ($wordCount >= 50 && $wordCount <= 150) {
            $score += 40;
        } elseif ($wordCount >= 30 && $wordCount <= 200) {
            $score += 25;
        } else {
            $score += 10;
        }
        
        // Check for keywords
        $keywords = $this->extractKeywords($content);
        $score += min(30, count($keywords) * 5);
        
        // Check for quantifiable achievements
        if (preg_match('/\d+%|\d+\+|\$\d+/', $content)) {
            $score += 20;
        }
        
        // Check for action verbs
        $actionVerbs = ['achieved', 'improved', 'increased', 'developed', 'managed', 'led', 'created'];
        foreach ($actionVerbs as $verb) {
            if (str_contains(strtolower($content), $verb)) {
                $score += 2;
            }
        }
        
        return min(100, $score);
    }

    /**
     * Calculate experience section score
     */
    protected function calculateExperienceScore(string $content): int
    {
        $score = 0;
        
        // Check for quantifiable achievements
        $quantifiableCount = preg_match_all('/\d+%|\d+\+|\$\d+|\d+x/', $content);
        $score += min(40, $quantifiableCount * 10);
        
        // Check for action verbs
        $actionVerbs = ['achieved', 'improved', 'increased', 'developed', 'managed', 'led', 'created', 'implemented', 'optimized'];
        $verbCount = 0;
        foreach ($actionVerbs as $verb) {
            if (str_contains(strtolower($content), $verb)) {
                $verbCount++;
            }
        }
        $score += min(30, $verbCount * 5);
        
        // Check for keywords
        $keywords = $this->extractKeywords($content);
        $score += min(30, count($keywords) * 3);
        
        return min(100, $score);
    }

    /**
     * Calculate education section score
     */
    protected function calculateEducationScore(string $content): int
    {
        $score = 50; // Base score
        
        // Check for degree information
        if (preg_match('/bachelor|master|phd|doctorate|associate/i', $content)) {
            $score += 25;
        }
        
        // Check for GPA (if mentioned and high)
        if (preg_match('/gpa\s*[:\-]?\s*([3-4]\.\d+|[3-4]\.?\d*)/i', $content)) {
            $score += 15;
        }
        
        // Check for relevant coursework or honors
        if (str_contains(strtolower($content), 'honor') || str_contains(strtolower($content), 'dean')) {
            $score += 10;
        }
        
        return min(100, $score);
    }

    /**
     * Calculate skills section score
     */
    protected function calculateSkillsScore(string $content): int
    {
        $skills = explode(',', $content);
        $skillCount = count(array_filter($skills, fn($skill) => strlen(trim($skill)) > 2));
        
        $score = 0;
        
        // Optimal skill count (8-15)
        if ($skillCount >= 8 && $skillCount <= 15) {
            $score += 50;
        } elseif ($skillCount >= 5 && $skillCount <= 20) {
            $score += 35;
        } else {
            $score += 20;
        }
        
        // Check for technical skills
        $technicalKeywords = ['python', 'java', 'javascript', 'sql', 'aws', 'azure', 'docker', 'kubernetes'];
        foreach ($technicalKeywords as $keyword) {
            if (str_contains(strtolower($content), $keyword)) {
                $score += 5;
            }
        }
        
        return min(100, $score);
    }

    /**
     * Calculate generic section score
     */
    protected function calculateGenericScore(string $content): int
    {
        $score = 50; // Base score
        
        // Length check
        $wordCount = str_word_count($content);
        if ($wordCount > 20) $score += 25;
        if ($wordCount > 50) $score += 15;
        
        // Keyword density
        $keywords = $this->extractKeywords($content);
        $score += min(10, count($keywords) * 2);
        
        return min(100, $score);
    }

    /**
     * Extract keywords from content
     */
    protected function extractKeywords(string $content): array
    {
        // Simple keyword extraction - can be enhanced with NLP
        $words = str_word_count(strtolower($content), 1);
        $keywords = array_filter($words, fn($word) => strlen($word) > 3);
        return array_unique($keywords);
    }

    /**
     * Get industry-specific keywords
     */
    protected function getIndustryKeywords(string $role): array
    {
        $roleKeywords = $this->industryKeywords[strtolower($role)] ?? [];
        return array_merge($roleKeywords, $this->atsKeywords['general']);
    }

    /**
     * Get keyword importance score
     */
    protected function getKeywordImportance(string $keyword, string $role): int
    {
        // Simple importance scoring - can be enhanced
        $importantKeywords = ['manager', 'senior', 'lead', 'director', 'analyst', 'developer', 'engineer'];
        return in_array(strtolower($keyword), $importantKeywords) ? 90 : 70;
    }

    /**
     * Generate keyword suggestion text
     */
    protected function generateKeywordSuggestion(string $keyword, string $role): string
    {
        return "Consider incorporating '{$keyword}' in your {$role} experience or skills section.";
    }

    /**
     * Generate keyword integration suggestion
     */
    protected function generateKeywordIntegration(string $keyword, string $sectionType): string
    {
        switch ($sectionType) {
            case 'summary':
                return "Incorporate '{$keyword}' into your professional summary.";
            case 'experience':
                return "Add '{$keyword}' to relevant job responsibilities or achievements.";
            case 'skills':
                return "Include '{$keyword}' in your skills list if applicable.";
            default:
                return "Consider adding '{$keyword}' to this section.";
        }
    }

    /**
     * Build AI analysis prompt
     */
    protected function buildAIAnalysisPrompt(string $sectionType, string $content): string
    {
        return "Analyze this resume {$sectionType} section for ATS optimization:\n\n{$content}\n\nProvide specific, actionable suggestions to improve ATS compatibility and keyword optimization.";
    }

    /**
     * Parse AI suggestions from response
     */
    protected function parseAISuggestions(string $response): array
    {
        // Simple parsing - can be enhanced with structured AI responses
        $suggestions = [];
        
        if (str_contains($response, 'keyword')) {
            $suggestions[] = [
                'type' => 'keyword',
                'priority' => 'medium',
                'title' => 'AI Keyword Suggestion',
                'description' => $response,
                'ats_impact' => 8,
                'reason' => 'AI-powered optimization',
            ];
        }
        
        return $suggestions;
    }

    /**
     * Get formatting recommendations
     */
    protected function getFormattingRecommendations(string $text): array
    {
        $recommendations = [];
        
        // Check for common formatting issues
        if (strlen($text) < 500) {
            $recommendations[] = [
                'type' => 'format',
                'priority' => 'medium',
                'title' => 'Resume too short',
                'description' => 'Your resume should typically be 1-2 pages long with substantial content.',
                'ats_impact' => 15,
            ];
        }
        
        return $recommendations;
    }

    /**
     * Get keyword density recommendations
     */
    protected function getKeywordDensityRecommendations(string $text): array
    {
        $recommendations = [];
        $wordCount = str_word_count($text);
        $keywords = $this->extractKeywords($text);
        $keywordDensity = count($keywords) / $wordCount * 100;
        
        if ($keywordDensity < 2) {
            $recommendations[] = [
                'type' => 'keyword',
                'priority' => 'high',
                'title' => 'Low keyword density',
                'description' => 'Add more industry-relevant keywords to improve ATS matching.',
                'ats_impact' => 20,
            ];
        }
        
        return $recommendations;
    }

    /**
     * Get structure recommendations
     */
    protected function getStructureRecommendations(string $text): array
    {
        $recommendations = [];
        
        // Check for common sections
        $sections = ['experience', 'education', 'skills'];
        foreach ($sections as $section) {
            if (!str_contains(strtolower($text), $section)) {
                $recommendations[] = [
                    'type' => 'structure',
                    'priority' => 'medium',
                    'title' => "Missing {$section} section",
                    'description' => "Consider adding a dedicated {$section} section.",
                    'ats_impact' => 10,
                ];
            }
        }
        
        return $recommendations;
    }

    /**
     * Load ATS keywords
     */
    protected function loadATSKeywords(): void
    {
        $this->atsKeywords = [
            'general' => ['results', 'achievement', 'improved', 'increased', 'managed', 'led', 'developed', 'created'],
            'technical' => ['programming', 'software', 'development', 'coding', 'testing', 'debugging'],
            'management' => ['leadership', 'team', 'project', 'strategy', 'planning', 'coordination'],
        ];
    }

    /**
     * Load industry-specific keywords
     */
    protected function loadIndustryKeywords(): void
    {
        $this->industryKeywords = [
            'software engineer' => ['python', 'java', 'javascript', 'react', 'node.js', 'sql', 'git', 'agile'],
            'data analyst' => ['sql', 'python', 'excel', 'tableau', 'statistics', 'visualization', 'reporting'],
            'marketing' => ['seo', 'social media', 'analytics', 'campaigns', 'content', 'digital marketing'],
            'sales' => ['revenue', 'targets', 'crm', 'client', 'negotiation', 'pipeline', 'closing'],
        ];
    }
}