<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\ResumeSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResumeSectionParserService
{
    protected array $sectionPatterns;
    protected array $sectionKeywords;

    public function __construct()
    {
        $this->initializeSectionPatterns();
        $this->initializeSectionKeywords();
    }

    /**
     * Parse resume text into structured sections
     */
    public function parseResumeIntoSections(Resume $resume, string $resumeText): Collection
    {
        $sections = collect();
        $rawSections = $this->extractSections($resumeText);

        foreach ($rawSections as $index => $rawSection) {
            $sectionType = $this->identifySectionType($rawSection['title'], $rawSection['content']);
            $parsedContent = $this->parseContentByType($sectionType, $rawSection['content']);

            $section = ResumeSection::create([
                'resume_id' => $resume->id,
                'section_type' => $sectionType,
                'title' => $rawSection['title'],
                'content' => $parsedContent,
                'order_index' => $index,
                'ats_score' => 0, // Will be calculated later
            ]);

            $sections->push($section);
        }

        return $sections;
    }

    /**
     * Parse existing resume content and create/update sections
     */
    public function updateResumeSections(Resume $resume, string $resumeText): Collection
    {
        // Delete existing sections
        $resume->sections()->delete();

        // Parse and create new sections
        return $this->parseResumeIntoSections($resume, $resumeText);
    }

    /**
     * Extract sections from resume text
     */
    protected function extractSections(string $text): array
    {
        $sections = [];
        $lines = explode("\n", $text);
        $currentSection = null;
        $currentContent = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }

            // Check if this line is a section header
            if ($this->isSectionHeader($line)) {
                // Save previous section if exists
                if ($currentSection) {
                    $sections[] = [
                        'title' => $currentSection,
                        'content' => implode("\n", $currentContent),
                    ];
                }

                // Start new section
                $currentSection = $this->cleanSectionTitle($line);
                $currentContent = [];
            } else {
                // Add content to current section
                $currentContent[] = $line;
            }
        }

        // Add the last section
        if ($currentSection) {
            $sections[] = [
                'title' => $currentSection,
                'content' => implode("\n", $currentContent),
            ];
        }

        // If no sections were found, create a default structure
        if (empty($sections)) {
            $sections = $this->createDefaultSections($text);
        }

        return $sections;
    }

    /**
     * Check if a line is a section header
     */
    protected function isSectionHeader(string $line): bool
    {
        // Check for common section header patterns
        foreach ($this->sectionPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        // Check for keyword-based headers
        $lineLower = strtolower($line);
        foreach ($this->sectionKeywords as $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lineLower, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Clean section title
     */
    protected function cleanSectionTitle(string $title): string
    {
        // Remove common formatting characters
        $title = preg_replace('/[•\-=_:]+/', '', $title);
        $title = trim($title);
        
        return $title;
    }

    /**
     * Identify section type based on title and content
     */
    protected function identifySectionType(string $title, string $content): string
    {
        $titleLower = strtolower($title);
        $contentLower = strtolower($content);

        // Contact Information
        if ($this->containsAny($titleLower, ['contact', 'personal']) || 
            $this->hasContactInfo($content)) {
            return 'contact';
        }

        // Professional Summary
        if ($this->containsAny($titleLower, ['summary', 'profile', 'objective', 'about'])) {
            return 'summary';
        }

        // Work Experience
        if ($this->containsAny($titleLower, ['experience', 'employment', 'work', 'career', 'professional'])) {
            return 'experience';
        }

        // Education
        if ($this->containsAny($titleLower, ['education', 'academic', 'university', 'college', 'school'])) {
            return 'education';
        }

        // Skills
        if ($this->containsAny($titleLower, ['skills', 'competencies', 'technical', 'technologies'])) {
            return 'skills';
        }

        // Projects
        if ($this->containsAny($titleLower, ['projects', 'portfolio', 'work samples'])) {
            return 'projects';
        }

        // Certifications
        if ($this->containsAny($titleLower, ['certifications', 'certificates', 'credentials', 'licenses'])) {
            return 'certifications';
        }

        // Achievements/Awards
        if ($this->containsAny($titleLower, ['achievements', 'awards', 'accomplishments', 'recognition'])) {
            return 'achievements';
        }

        // Languages
        if ($this->containsAny($titleLower, ['languages', 'linguistic'])) {
            return 'languages';
        }

        // Volunteer Experience
        if ($this->containsAny($titleLower, ['volunteer', 'community', 'service'])) {
            return 'volunteer';
        }

        // Default to generic section
        return 'other';
    }

    /**
     * Parse content based on section type
     */
    protected function parseContentByType(string $sectionType, string $content): array
    {
        switch ($sectionType) {
            case 'contact':
                return $this->parseContactSection($content);
            case 'summary':
                return $this->parseSummarySection($content);
            case 'experience':
                return $this->parseExperienceSection($content);
            case 'education':
                return $this->parseEducationSection($content);
            case 'skills':
                return $this->parseSkillsSection($content);
            case 'projects':
                return $this->parseProjectsSection($content);
            case 'certifications':
                return $this->parseCertificationsSection($content);
            default:
                return $this->parseGenericSection($content);
        }
    }

    /**
     * Parse contact information section
     */
    protected function parseContactSection(string $content): array
    {
        $contact = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'location' => '',
            'linkedin' => '',
            'website' => '',
            'github' => '',
        ];

        // Extract email
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content, $matches)) {
            $contact['email'] = $matches[0];
        }

        // Extract phone number
        if (preg_match('/(\+?\d[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $content, $matches)) {
            $contact['phone'] = $matches[0];
        }

        // Extract LinkedIn
        if (preg_match('/linkedin\.com\/in\/[a-zA-Z0-9-]+/', $content, $matches)) {
            $contact['linkedin'] = 'https://' . $matches[0];
        }

        // Extract GitHub
        if (preg_match('/github\.com\/[a-zA-Z0-9-]+/', $content, $matches)) {
            $contact['github'] = 'https://' . $matches[0];
        }

        // Extract location (simple pattern)
        if (preg_match('/([A-Z][a-z]+,?\s+[A-Z]{2})|([A-Z][a-z]+,?\s+[A-Z][a-z]+)/', $content, $matches)) {
            $contact['location'] = $matches[0];
        }

        $contact['raw_content'] = $content;

        return $contact;
    }

    /**
     * Parse summary section
     */
    protected function parseSummarySection(string $content): array
    {
        return [
            'text' => trim($content),
            'word_count' => str_word_count($content),
            'keywords' => $this->extractKeywords($content),
        ];
    }

    /**
     * Parse experience section
     */
    protected function parseExperienceSection(string $content): array
    {
        $experiences = [];
        $blocks = $this->splitIntoBlocks($content);

        foreach ($blocks as $block) {
            $experience = [
                'company' => '',
                'position' => '',
                'duration' => '',
                'location' => '',
                'description' => '',
                'achievements' => [],
            ];

            $lines = explode("\n", $block);
            $descriptionLines = [];

            foreach ($lines as $index => $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // First line is usually position/company
                if ($index === 0) {
                    if (str_contains($line, ' at ') || str_contains($line, ' | ')) {
                        $parts = preg_split('/ at | \| /', $line, 2);
                        $experience['position'] = trim($parts[0]);
                        $experience['company'] = trim($parts[1] ?? '');
                    } else {
                        $experience['position'] = $line;
                    }
                }
                // Look for date patterns
                elseif (preg_match('/\b(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|\d{4})/i', $line)) {
                    $experience['duration'] = $line;
                }
                // Bullet points are achievements
                elseif (str_starts_with($line, '•') || str_starts_with($line, '-') || str_starts_with($line, '*')) {
                    $experience['achievements'][] = ltrim($line, '•-* ');
                }
                // Everything else is description
                else {
                    $descriptionLines[] = $line;
                }
            }

            $experience['description'] = implode("\n", $descriptionLines);
            
            if (!empty($experience['position']) || !empty($experience['company'])) {
                $experiences[] = $experience;
            }
        }

        return ['experiences' => $experiences];
    }

    /**
     * Parse education section
     */
    protected function parseEducationSection(string $content): array
    {
        $educations = [];
        $blocks = $this->splitIntoBlocks($content);

        foreach ($blocks as $block) {
            $education = [
                'degree' => '',
                'institution' => '',
                'graduation_date' => '',
                'gpa' => '',
                'location' => '',
                'details' => '',
            ];

            $lines = explode("\n", $block);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Look for degree patterns
                if (preg_match('/\b(bachelor|master|phd|doctorate|associate|diploma|certificate)/i', $line)) {
                    $education['degree'] = $line;
                }
                // Look for GPA
                elseif (preg_match('/gpa[:\s]*([0-4]\.\d+)/i', $line, $matches)) {
                    $education['gpa'] = $matches[1];
                }
                // Look for dates
                elseif (preg_match('/\b(19|20)\d{2}\b/', $line)) {
                    $education['graduation_date'] = $line;
                }
                // Institution name (usually the line without degree keywords)
                elseif (empty($education['institution']) && !preg_match('/\b(19|20)\d{2}\b/', $line)) {
                    $education['institution'] = $line;
                }
            }

            if (!empty($education['degree']) || !empty($education['institution'])) {
                $educations[] = $education;
            }
        }

        return ['educations' => $educations];
    }

    /**
     * Parse skills section
     */
    protected function parseSkillsSection(string $content): array
    {
        $skills = [];
        
        // Split by common delimiters
        $skillText = preg_replace('/[•\-*]/', ',', $content);
        $skillItems = preg_split('/[,\n]/', $skillText);
        
        foreach ($skillItems as $skill) {
            $skill = trim($skill);
            if (!empty($skill) && strlen($skill) > 1) {
                $skills[] = $skill;
            }
        }

        return [
            'skills' => array_unique($skills),
            'categories' => $this->categorizeSkills($skills),
        ];
    }

    /**
     * Parse projects section
     */
    protected function parseProjectsSection(string $content): array
    {
        $projects = [];
        $blocks = $this->splitIntoBlocks($content);

        foreach ($blocks as $block) {
            $project = [
                'name' => '',
                'description' => '',
                'technologies' => [],
                'url' => '',
                'duration' => '',
            ];

            $lines = explode("\n", $block);
            
            foreach ($lines as $index => $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if ($index === 0) {
                    $project['name'] = $line;
                } elseif (str_contains($line, 'http')) {
                    $project['url'] = $line;
                } else {
                    $project['description'] .= $line . "\n";
                }
            }

            $project['description'] = trim($project['description']);
            $project['technologies'] = $this->extractTechnologies($block);

            if (!empty($project['name'])) {
                $projects[] = $project;
            }
        }

        return ['projects' => $projects];
    }

    /**
     * Parse certifications section
     */
    protected function parseCertificationsSection(string $content): array
    {
        $certifications = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $certification = [
                'name' => $line,
                'issuer' => '',
                'date' => '',
                'credential_id' => '',
            ];

            // Extract issuer if mentioned
            if (str_contains($line, ' - ') || str_contains($line, ' | ')) {
                $parts = preg_split('/ - | \| /', $line, 2);
                $certification['name'] = trim($parts[0]);
                $certification['issuer'] = trim($parts[1]);
            }

            $certifications[] = $certification;
        }

        return ['certifications' => $certifications];
    }

    /**
     * Parse generic section
     */
    protected function parseGenericSection(string $content): array
    {
        return [
            'text' => trim($content),
            'items' => $this->extractListItems($content),
        ];
    }

    /**
     * Helper method to check if text contains any of the given keywords
     */
    protected function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if content has contact information
     */
    protected function hasContactInfo(string $content): bool
    {
        return preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $content) ||
               preg_match('/\d{3}[-.]?\d{3}[-.]?\d{4}/', $content);
    }

    /**
     * Split content into logical blocks
     */
    protected function splitIntoBlocks(string $content): array
    {
        // Split by double line breaks or clear separators
        $blocks = preg_split('/\n\s*\n|\n-{3,}|\n={3,}/', $content);
        return array_filter(array_map('trim', $blocks));
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

    /**
     * Categorize skills
     */
    protected function categorizeSkills(array $skills): array
    {
        $categories = [
            'technical' => [],
            'soft' => [],
            'languages' => [],
            'tools' => [],
        ];

        $technicalKeywords = ['python', 'java', 'javascript', 'php', 'sql', 'html', 'css', 'react', 'angular', 'vue'];
        $toolKeywords = ['git', 'docker', 'kubernetes', 'jenkins', 'jira', 'confluence', 'slack'];
        $languageKeywords = ['english', 'spanish', 'french', 'german', 'mandarin', 'japanese'];

        foreach ($skills as $skill) {
            $skillLower = strtolower($skill);
            
            if ($this->containsAny($skillLower, $technicalKeywords)) {
                $categories['technical'][] = $skill;
            } elseif ($this->containsAny($skillLower, $toolKeywords)) {
                $categories['tools'][] = $skill;
            } elseif ($this->containsAny($skillLower, $languageKeywords)) {
                $categories['languages'][] = $skill;
            } else {
                $categories['soft'][] = $skill;
            }
        }

        return $categories;
    }

    /**
     * Extract technologies from project description
     */
    protected function extractTechnologies(string $text): array
    {
        $techKeywords = ['python', 'java', 'javascript', 'react', 'angular', 'vue', 'php', 'laravel', 'symfony', 'django', 'flask'];
        $technologies = [];
        
        foreach ($techKeywords as $tech) {
            if (stripos($text, $tech) !== false) {
                $technologies[] = ucfirst($tech);
            }
        }

        return array_unique($technologies);
    }

    /**
     * Extract list items from content
     */
    protected function extractListItems(string $content): array
    {
        $items = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '•') || str_starts_with($line, '-') || str_starts_with($line, '*')) {
                $items[] = ltrim($line, '•-* ');
            }
        }

        return $items;
    }

    /**
     * Create default sections when none are found
     */
    protected function createDefaultSections(string $text): array
    {
        $wordCount = str_word_count($text);
        
        if ($wordCount < 50) {
            return [
                [
                    'title' => 'Content',
                    'content' => $text,
                ]
            ];
        }

        // Try to split into paragraphs
        $paragraphs = array_filter(explode("\n\n", $text));
        
        if (count($paragraphs) > 1) {
            $sections = [];
            foreach ($paragraphs as $index => $paragraph) {
                $sections[] = [
                    'title' => 'Section ' . ($index + 1),
                    'content' => trim($paragraph),
                ];
            }
            return $sections;
        }

        // Fallback to single section
        return [
            [
                'title' => 'Resume Content',
                'content' => $text,
            ]
        ];
    }

    /**
     * Initialize section patterns
     */
    protected function initializeSectionPatterns(): void
    {
        $this->sectionPatterns = [
            '/^[A-Z\s]{2,}$/',  // ALL CAPS headers
            '/^[A-Z][a-z\s]+:?$/',  // Title case headers
            '/^[-=]{3,}$/',  // Separator lines
            '/^\d+\.\s+[A-Z]/',  // Numbered sections
        ];
    }

    /**
     * Initialize section keywords
     */
    protected function initializeSectionKeywords(): void
    {
        $this->sectionKeywords = [
            'contact' => ['contact', 'personal', 'information'],
            'summary' => ['summary', 'profile', 'objective', 'about'],
            'experience' => ['experience', 'employment', 'work', 'career', 'professional'],
            'education' => ['education', 'academic', 'university', 'college', 'school'],
            'skills' => ['skills', 'competencies', 'technical', 'technologies'],
            'projects' => ['projects', 'portfolio', 'work samples'],
            'certifications' => ['certifications', 'certificates', 'credentials', 'licenses'],
            'achievements' => ['achievements', 'awards', 'accomplishments', 'recognition'],
            'languages' => ['languages', 'linguistic'],
            'volunteer' => ['volunteer', 'community', 'service'],
        ];
    }
}