<?php

namespace App\Services;

use Anthropic\Laravel\Facades\Anthropic;

class AnthropicService
{
    public function analyzeResume(string $resumeText, array $options = []): array
    {
        $prompt = $this->buildAnalysisPrompt($resumeText, $options);

        $response = Anthropic::messages()
            ->create([
                'model' => 'claude-3-5-sonnet-20241022',
                'max_tokens' => 4000,
                'system' => 'You are an expert resume analyzer and career consultant. Analyze resumes with precision and provide actionable insights.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

        return $this->parseAnalysisResponse($response);
    }

    public function optimizeResumeForJob(string $resumeText, string $jobDescription): array
    {
        $prompt = $this->buildJobMatchPrompt($resumeText, $jobDescription);

        $response = Anthropic::messages()
            ->create([
                'model' => 'claude-3-5-sonnet-20241022',
                'max_tokens' => 4000,
                'system' => 'You are a resume optimization expert. Help candidates tailor their resumes for specific job opportunities.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

        return $this->parseOptimizationResponse($response);
    }

    public function extractSkills(string $resumeText): array
    {
        $prompt = "Extract all skills, technologies, and competencies from this resume. Categorize them as:\n\n";
        $prompt .= "1. Technical Skills (programming languages, frameworks, tools)\n";
        $prompt .= "2. Soft Skills (communication, leadership, etc.)\n";
        $prompt .= "3. Certifications\n";
        $prompt .= "4. Languages\n\n";
        $prompt .= "Resume text:\n{$resumeText}\n\n";
        $prompt .= "Return the response as a structured JSON with these categories.";

        $response = Anthropic::messages()
            ->create([
                'model' => 'claude-3-5-sonnet-20241022',
                'max_tokens' => 2000,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

        return $this->parseSkillsResponse($response);
    }

    public function analyzeText(string $prompt): string
    {
        $response = Anthropic::messages()
            ->create([
                'model' => 'claude-3-5-sonnet-20241022',
                'max_tokens' => 2000,
                'system' => 'You are an expert resume and content analyzer. Provide detailed, actionable analysis and suggestions.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

        return $response['content'][0]['text'] ?? '';
    }

    private function buildAnalysisPrompt(string $resumeText, array $options): string
    {
        $analysisDepth = $options['depth'] ?? 'comprehensive';
        $targetRole = $options['target_role'] ?? null;
        $targetIndustry = $options['target_industry'] ?? null;

        $prompt = "Analyze this resume comprehensively and provide detailed feedback:\n\n";
        $prompt .= "Resume Text:\n{$resumeText}\n\n";

        if ($targetRole) {
            $prompt .= "Target Role: {$targetRole}\n";
        }

        if ($targetIndustry) {
            $prompt .= "Target Industry: {$targetIndustry}\n";
        }

        $prompt .= "\nProvide analysis in the following structure:\n\n";
        $prompt .= "1. OVERALL SCORE (0-100)\n";
        $prompt .= "2. DETAILED SCORES:\n";
        $prompt .= "   - ATS Compatibility (0-100)\n";
        $prompt .= "   - Content Quality (0-100)\n";
        $prompt .= "   - Format & Structure (0-100)\n";
        $prompt .= "   - Keyword Optimization (0-100)\n\n";
        $prompt .= "3. STRENGTHS (3-5 key strengths)\n";
        $prompt .= "4. WEAKNESSES (3-5 areas for improvement)\n";
        $prompt .= "5. RECOMMENDATIONS (specific, actionable advice)\n";
        $prompt .= "6. MISSING SKILLS (skills that would benefit the candidate)\n";
        $prompt .= "7. ATS OPTIMIZATION TIPS\n\n";
        $prompt .= "Format the response as structured JSON for easy parsing.";

        return $prompt;
    }

    private function buildJobMatchPrompt(string $resumeText, string $jobDescription): string
    {
        $prompt = "Compare this resume against the job description and provide optimization recommendations:\n\n";
        $prompt .= "RESUME:\n{$resumeText}\n\n";
        $prompt .= "JOB DESCRIPTION:\n{$jobDescription}\n\n";
        $prompt .= "Provide analysis in JSON format with:\n";
        $prompt .= "1. compatibility_score (0-100)\n";
        $prompt .= "2. matching_skills (array)\n";
        $prompt .= "3. missing_skills (array)\n";
        $prompt .= "4. recommended_additions (specific content to add)\n";
        $prompt .= "5. keyword_gaps (missing keywords from job description)\n";
        $prompt .= "6. experience_match (how well experience aligns)\n";
        $prompt .= "7. optimization_priority (top 5 changes to make)\n";

        return $prompt;
    }

    private function parseAnalysisResponse($response): array
    {
        $content = $response['content'][0]['text'] ?? '';

        // Try to extract JSON from the response
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                return $json;
            }
        }

        // Fallback parsing if JSON extraction fails
        return [
            'overall_score' => $this->extractScore($content, 'overall'),
            'ats_score' => $this->extractScore($content, 'ats'),
            'content_score' => $this->extractScore($content, 'content'),
            'format_score' => $this->extractScore($content, 'format'),
            'keyword_score' => $this->extractScore($content, 'keyword'),
            'analysis_text' => $content,
            'recommendations' => $this->extractRecommendations($content),
            'strengths' => $this->extractStrengths($content),
            'weaknesses' => $this->extractWeaknesses($content),
        ];
    }

    private function parseOptimizationResponse($response): array
    {
        $content = $response['content'][0]['text'] ?? '';

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                return $json;
            }
        }

        return [
            'compatibility_score' => 75, // default
            'optimization_text' => $content,
            'matching_skills' => [],
            'missing_skills' => [],
            'recommended_additions' => [],
        ];
    }

    private function parseSkillsResponse($response): array
    {
        $content = $response['content'][0]['text'] ?? '';

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                return $json;
            }
        }

        return [
            'technical_skills' => [],
            'soft_skills' => [],
            'certifications' => [],
            'languages' => [],
        ];
    }

    private function extractScore(string $content, string $type): int
    {
        $patterns = [
            "/#{$type}.*?(\d+)/i",
            "/{$type}.*?(\d+)/i",
            "/(\d+).*?{$type}/i",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $score = (int)$matches[1];
                return min(100, max(0, $score));
            }
        }

        return 75; // Default score
    }

    private function extractRecommendations(string $content): array
    {
        if (preg_match('/recommendations?:?\s*(.*?)(?=\n\n|\n[A-Z]|$)/is', $content, $matches)) {
            $text = $matches[1];
            return array_filter(array_map('trim', explode("\n", $text)));
        }

        return [];
    }

    private function extractStrengths(string $content): array
    {
        if (preg_match('/strengths?:?\s*(.*?)(?=\n\n|\n[A-Z]|$)/is', $content, $matches)) {
            $text = $matches[1];
            return array_filter(array_map('trim', explode("\n", $text)));
        }

        return [];
    }

    private function extractWeaknesses(string $content): array
    {
        if (preg_match('/weaknesses?:?\s*(.*?)(?=\n\n|\n[A-Z]|$)/is', $content, $matches)) {
            $text = $matches[1];
            return array_filter(array_map('trim', explode("\n", $text)));
        }

        return [];
    }
}