<?php

namespace App\Jobs;

use App\Models\Resume;
use App\Models\Tenant;
use App\Services\AnthropicService;
use App\Services\FileProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessResumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Resume $resume,
        private bool $analysisOnly = false
    ) {}

    public function handle(
        FileProcessingService $fileProcessingService,
        AnthropicService $anthropicService
    ): void {
        try {
            // Make sure we're in the correct tenant context
            $this->setTenantContext();

            if (!$this->analysisOnly) {
                $this->parseResume($fileProcessingService);
            }

            if ($this->resume->parsing_status === 'completed') {
                $this->analyzeResume($anthropicService);
            }

        } catch (Exception $e) {
            Log::error('Resume processing failed', [
                'resume_id' => $this->resume->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->handleFailure($e);
        }
    }

    private function setTenantContext(): void
    {
        // Extract tenant ID from resume metadata or storage path
        $pathParts = explode('/', $this->resume->storage_path);
        if (isset($pathParts[1])) {
            $tenantId = $pathParts[1];
            $tenant = Tenant::find($tenantId);

            if ($tenant) {
                $tenant->makeCurrent();
            }
        }
    }

    private function parseResume(FileProcessingService $fileProcessingService): void
    {
        Log::info('Starting resume parsing', ['resume_id' => $this->resume->id]);

        $this->resume->update(['parsing_status' => 'processing']);

        try {
            $parsedData = $fileProcessingService->parseResume($this->resume);

            // Store parsed data in metadata
            $this->resume->update([
                'parsing_status' => 'completed',
                'metadata' => array_merge($this->resume->metadata ?? [], [
                    'parsed_data' => $parsedData,
                    'parsed_at' => now()->toISOString(),
                ]),
            ]);

            Log::info('Resume parsing completed', [
                'resume_id' => $this->resume->id,
                'word_count' => $parsedData['metadata']['word_count'] ?? 0,
            ]);

        } catch (Exception $e) {
            $this->resume->update(['parsing_status' => 'failed']);
            throw $e;
        }
    }

    private function analyzeResume(AnthropicService $anthropicService): void
    {
        Log::info('Starting resume analysis', ['resume_id' => $this->resume->id]);

        $this->resume->update(['analysis_status' => 'processing']);

        try {
            $parsedData = $this->resume->metadata['parsed_data'] ?? null;

            if (!$parsedData || !isset($parsedData['raw_text'])) {
                throw new Exception('No parsed text available for analysis');
            }

            $resumeText = $parsedData['raw_text'];

            // Prepare analysis options
            $options = [
                'depth' => 'comprehensive',
                'target_role' => $this->resume->metadata['target_role'] ?? null,
                'target_industry' => $this->resume->metadata['target_industry'] ?? null,
            ];

            // Perform AI analysis
            $analysisResult = $anthropicService->analyzeResume($resumeText, $options);

            // Extract skills using AI
            $skillsResult = $anthropicService->extractSkills($resumeText);

            // Calculate scores
            $scores = $this->calculateScores($analysisResult, $parsedData);

            // Create analysis result record
            $this->resume->analysisResults()->create([
                'analysis_type' => 'comprehensive',
                'overall_score' => $scores['overall'],
                'ats_score' => $scores['ats'],
                'content_score' => $scores['content'],
                'format_score' => $scores['format'],
                'keyword_score' => $scores['keyword'],
                'detailed_scores' => $scores,
                'recommendations' => $analysisResult['recommendations'] ?? [],
                'extracted_skills' => $skillsResult,
                'missing_skills' => $analysisResult['missing_skills'] ?? [],
                'keywords' => $analysisResult['keywords'] ?? [],
                'sections_analysis' => $this->analyzeSections($parsedData),
                'ai_insights' => $analysisResult['analysis_text'] ?? null,
            ]);

            $this->resume->update(['analysis_status' => 'completed']);

            Log::info('Resume analysis completed', [
                'resume_id' => $this->resume->id,
                'overall_score' => $scores['overall'],
            ]);

        } catch (Exception $e) {
            $this->resume->update(['analysis_status' => 'failed']);
            throw $e;
        }
    }

    private function calculateScores(array $analysisResult, array $parsedData): array
    {
        // Extract scores from AI analysis or calculate defaults
        $scores = [
            'overall' => $analysisResult['overall_score'] ?? $this->calculateOverallScore($parsedData),
            'ats' => $analysisResult['ats_score'] ?? $this->calculateATSScore($parsedData),
            'content' => $analysisResult['content_score'] ?? $this->calculateContentScore($parsedData),
            'format' => $analysisResult['format_score'] ?? $this->calculateFormatScore($parsedData),
            'keyword' => $analysisResult['keyword_score'] ?? $this->calculateKeywordScore($parsedData),
        ];

        // Ensure scores are between 0 and 100
        foreach ($scores as $key => $score) {
            $scores[$key] = max(0, min(100, (int) $score));
        }

        // Calculate overall score if not provided
        if (!isset($analysisResult['overall_score'])) {
            $scores['overall'] = (int) round(
                ($scores['ats'] + $scores['content'] + $scores['format'] + $scores['keyword']) / 4
            );
        }

        return $scores;
    }

    private function calculateOverallScore(array $parsedData): int
    {
        $score = 70; // Base score

        // Check for contact information
        if (!empty($parsedData['entities']['emails'])) $score += 5;
        if (!empty($parsedData['entities']['phones'])) $score += 5;

        // Check for key sections
        if (!empty($parsedData['sections']['experience'])) $score += 10;
        if (!empty($parsedData['sections']['education'])) $score += 5;
        if (!empty($parsedData['sections']['skills'])) $score += 5;

        return min(100, $score);
    }

    private function calculateATSScore(array $parsedData): int
    {
        $score = 60; // Base ATS score

        $text = $parsedData['raw_text'] ?? '';

        // Check for standard sections
        $sections = ['experience', 'education', 'skills'];
        foreach ($sections as $section) {
            if (!empty($parsedData['sections'][$section])) {
                $score += 8;
            }
        }

        // Check for contact info
        if (!empty($parsedData['entities']['emails'])) $score += 5;
        if (!empty($parsedData['entities']['phones'])) $score += 5;

        // Check for dates (experience timeline)
        if (preg_match_all('/\b\d{4}\b/', $text) >= 2) $score += 5;

        return min(100, $score);
    }

    private function calculateContentScore(array $parsedData): int
    {
        $score = 50;

        $wordCount = $parsedData['metadata']['word_count'] ?? 0;

        // Optimal word count
        if ($wordCount >= 300 && $wordCount <= 800) {
            $score += 20;
        } elseif ($wordCount >= 200) {
            $score += 10;
        }

        // Check for quantified achievements
        $text = $parsedData['raw_text'] ?? '';
        $quantifiers = preg_match_all('/\b\d+%|\$\d+|\d+\s*million|\d+\s*billion|\d+x\b/i', $text);
        $score += min(15, $quantifiers * 3);

        // Check for action verbs
        $actionVerbs = ['managed', 'led', 'developed', 'created', 'implemented', 'improved', 'increased', 'achieved'];
        foreach ($actionVerbs as $verb) {
            if (stripos($text, $verb) !== false) {
                $score += 2;
            }
        }

        return min(100, $score);
    }

    private function calculateFormatScore(array $parsedData): int
    {
        $score = 70; // Assume decent formatting for parsed documents

        $sections = $parsedData['sections'] ?? [];
        $entities = $parsedData['entities'] ?? [];

        // Check for clear sections
        if (count($sections) >= 4) $score += 10;
        if (count($sections) >= 6) $score += 5;

        // Check for contact information formatting
        if (!empty($entities['emails']) && !empty($entities['phones'])) {
            $score += 10;
        }

        // Check for consistent structure
        if (!empty($sections['experience']) && !empty($sections['education'])) {
            $score += 5;
        }

        return min(100, $score);
    }

    private function calculateKeywordScore(array $parsedData): int
    {
        $score = 60;

        $skills = $parsedData['entities']['skills'] ?? [];
        $sectionSkills = $parsedData['sections']['skills'] ?? [];

        // More skills = higher keyword score
        $totalSkills = count($skills) + count($sectionSkills);
        $score += min(30, $totalSkills * 2);

        // Check for industry-relevant keywords
        $text = strtolower($parsedData['raw_text'] ?? '');
        $techKeywords = ['api', 'database', 'framework', 'agile', 'scrum', 'cloud', 'devops'];

        foreach ($techKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $score += 2;
            }
        }

        return min(100, $score);
    }

    private function analyzeSections(array $parsedData): array
    {
        $sections = $parsedData['sections'] ?? [];
        $analysis = [];

        foreach ($sections as $sectionName => $sectionData) {
            $analysis[$sectionName] = [
                'present' => !empty($sectionData),
                'quality' => $this->assessSectionQuality($sectionName, $sectionData),
                'recommendations' => $this->getSectionRecommendations($sectionName, $sectionData),
            ];
        }

        return $analysis;
    }

    private function assessSectionQuality(string $sectionName, $sectionData): string
    {
        if (empty($sectionData)) {
            return 'missing';
        }

        $content = is_array($sectionData) ? implode(' ', $sectionData) : (string) $sectionData;
        $length = strlen($content);

        return match ($sectionName) {
            'experience' => $length > 300 ? 'excellent' : ($length > 150 ? 'good' : 'needs_improvement'),
            'education' => $length > 100 ? 'good' : 'basic',
            'skills' => (is_array($sectionData) ? count($sectionData) : substr_count($content, ',')) > 10 ? 'excellent' : 'good',
            'summary' => $length > 100 && $length < 300 ? 'good' : 'needs_improvement',
            default => $length > 50 ? 'good' : 'basic',
        };
    }

    private function getSectionRecommendations(string $sectionName, $sectionData): array
    {
        if (empty($sectionData)) {
            return ["Add a {$sectionName} section to improve your resume."];
        }

        return match ($sectionName) {
            'experience' => [
                'Use action verbs to start bullet points',
                'Quantify achievements with numbers and percentages',
                'Focus on results and impact rather than just responsibilities',
            ],
            'skills' => [
                'Include both technical and soft skills',
                'Organize skills by category (e.g., Programming Languages, Tools)',
                'Match skills to job requirements',
            ],
            'summary' => [
                'Keep it concise (2-3 sentences)',
                'Highlight your unique value proposition',
                'Include years of experience and key specializations',
            ],
            default => ['Ensure this section is clear and relevant to your target role'],
        };
    }

    private function handleFailure(Exception $e): void
    {
        $this->resume->update([
            'parsing_status' => $this->analysisOnly ? $this->resume->parsing_status : 'failed',
            'analysis_status' => 'failed',
            'metadata' => array_merge($this->resume->metadata ?? [], [
                'error' => $e->getMessage(),
                'failed_at' => now()->toISOString(),
            ]),
        ]);
    }

    public function failed(Exception $exception): void
    {
        Log::error('ProcessResumeJob failed', [
            'resume_id' => $this->resume->id,
            'error' => $exception->getMessage(),
        ]);

        $this->handleFailure($exception);
    }
}