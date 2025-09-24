<?php

namespace App\Services;

use App\Models\Resume;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;

class ExportService
{
    /**
     * Export resume in various formats
     */
    public function exportResume(Resume $resume, string $format, array $options = []): array
    {
        try {
            $content = $resume->parsed_content ?? '';
            $analysis = $resume->latestAnalysis;

            switch (strtolower($format)) {
                case 'pdf':
                    return $this->exportToPdf($resume, $content, $analysis, $options);
                case 'docx':
                    return $this->exportToDocx($resume, $content, $analysis, $options);
                case 'html':
                    return $this->exportToHtml($resume, $content, $analysis, $options);
                case 'json':
                    return $this->exportToJson($resume, $content, $analysis, $options);
                case 'txt':
                    return $this->exportToText($resume, $content, $analysis, $options);
                case 'linkedin':
                    return $this->exportToLinkedIn($resume, $content, $analysis, $options);
                case 'ats':
                    return $this->exportATSFriendly($resume, $content, $analysis, $options);
                default:
                    throw new Exception("Unsupported export format: {$format}");
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export to PDF with customizable templates
     */
    private function exportToPdf(Resume $resume, string $content, $analysis, array $options): array
    {
        $template = $options['template'] ?? 'modern';
        $includeAnalysis = $options['include_analysis'] ?? false;

        // Generate HTML content
        $htmlContent = View::make('exports.pdf.resume', [
            'resume' => $resume,
            'content' => $content,
            'analysis' => $includeAnalysis ? $analysis : null,
            'template' => $template,
            'options' => $options,
        ])->render();

        // Convert HTML to PDF using various methods
        $pdfPath = $this->convertHtmlToPdf($htmlContent, $resume->id, $options);

        return [
            'success' => true,
            'file_path' => $pdfPath,
            'download_url' => Storage::url($pdfPath),
            'format' => 'pdf',
            'template' => $template,
        ];
    }

    /**
     * Export to DOCX with formatting
     */
    private function exportToDocx(Resume $resume, string $content, $analysis, array $options): array
    {
        $phpWord = new PhpWord();

        // Set document properties
        $properties = $phpWord->getDocInfo();
        $properties->setTitle($resume->original_filename);
        $properties->setCreator('AI Resume Analyzer');
        $properties->setDescription('Resume exported from AI Resume Analyzer');

        // Create section
        $section = $phpWord->addSection([
            'marginTop' => 720,
            'marginRight' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
        ]);

        // Add styles
        $phpWord->addTitleStyle(1, ['name' => 'Arial', 'size' => 16, 'bold' => true]);
        $phpWord->addTitleStyle(2, ['name' => 'Arial', 'size' => 14, 'bold' => true]);
        $phpWord->addFontStyle('normal', ['name' => 'Arial', 'size' => 11]);

        // Process content sections
        $sections = $this->parseResumeContent($content);

        foreach ($sections as $sectionTitle => $sectionContent) {
            if (!empty($sectionTitle) && $sectionTitle !== 'content') {
                $section->addTitle($sectionTitle, 2);
            }

            $section->addText($sectionContent, 'normal');
            $section->addTextBreak();
        }

        // Add analysis if requested
        if ($options['include_analysis'] ?? false && $analysis) {
            $section->addPageBreak();
            $section->addTitle('Resume Analysis Report', 1);

            $section->addText("Overall Score: {$analysis->overall_score}/100", ['bold' => true]);
            $section->addTextBreak();

            if ($analysis->recommendations) {
                $section->addTitle('Recommendations', 2);
                foreach ($analysis->recommendations as $recommendation) {
                    $section->addText("• {$recommendation}", 'normal');
                }
            }
        }

        // Save file
        $filename = "resume_{$resume->id}_" . time() . '.docx';
        $filePath = "exports/docx/{$filename}";
        $fullPath = storage_path("app/{$filePath}");

        // Ensure directory exists
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($fullPath);

        return [
            'success' => true,
            'file_path' => $filePath,
            'download_url' => Storage::url($filePath),
            'format' => 'docx',
        ];
    }

    /**
     * Export to HTML with responsive design
     */
    private function exportToHtml(Resume $resume, string $content, $analysis, array $options): array
    {
        $template = $options['template'] ?? 'modern';
        $standalone = $options['standalone'] ?? true;

        $htmlContent = View::make('exports.html.resume', [
            'resume' => $resume,
            'content' => $content,
            'analysis' => $options['include_analysis'] ? $analysis : null,
            'template' => $template,
            'standalone' => $standalone,
            'options' => $options,
        ])->render();

        $filename = "resume_{$resume->id}_" . time() . '.html';
        $filePath = "exports/html/{$filename}";

        Storage::put($filePath, $htmlContent);

        return [
            'success' => true,
            'file_path' => $filePath,
            'download_url' => Storage::url($filePath),
            'format' => 'html',
            'template' => $template,
        ];
    }

    /**
     * Export to JSON format for API consumption
     */
    private function exportToJson(Resume $resume, string $content, $analysis, array $options): array
    {
        $sections = $this->parseResumeContent($content);
        $extractedData = $this->extractStructuredData($content);

        $data = [
            'resume' => [
                'id' => $resume->id,
                'filename' => $resume->original_filename,
                'upload_date' => $resume->created_at->toISOString(),
                'last_updated' => $resume->updated_at->toISOString(),
            ],
            'content' => [
                'raw' => $content,
                'sections' => $sections,
                'structured' => $extractedData,
            ],
            'metadata' => [
                'word_count' => str_word_count($content),
                'character_count' => strlen($content),
                'export_date' => now()->toISOString(),
                'export_format' => 'json',
            ],
        ];

        // Include analysis if requested
        if ($options['include_analysis'] ?? false && $analysis) {
            $data['analysis'] = [
                'scores' => [
                    'overall' => $analysis->overall_score,
                    'ats' => $analysis->ats_score,
                    'content' => $analysis->content_score,
                    'format' => $analysis->format_score,
                    'keyword' => $analysis->keyword_score,
                ],
                'recommendations' => $analysis->recommendations,
                'extracted_skills' => $analysis->extracted_skills,
                'missing_skills' => $analysis->missing_skills,
                'keywords' => $analysis->keywords,
                'analysis_date' => $analysis->created_at->toISOString(),
            ];
        }

        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $filename = "resume_{$resume->id}_" . time() . '.json';
        $filePath = "exports/json/{$filename}";

        Storage::put($filePath, $jsonContent);

        return [
            'success' => true,
            'file_path' => $filePath,
            'download_url' => Storage::url($filePath),
            'format' => 'json',
            'data' => $data,
        ];
    }

    /**
     * Export to plain text
     */
    private function exportToText(Resume $resume, string $content, $analysis, array $options): array
    {
        $textContent = $content;

        // Add analysis if requested
        if ($options['include_analysis'] ?? false && $analysis) {
            $textContent .= "\n\n" . str_repeat("=", 50) . "\n";
            $textContent .= "RESUME ANALYSIS REPORT\n";
            $textContent .= str_repeat("=", 50) . "\n\n";
            $textContent .= "Overall Score: {$analysis->overall_score}/100\n";
            $textContent .= "ATS Score: {$analysis->ats_score}/100\n";
            $textContent .= "Content Score: {$analysis->content_score}/100\n";
            $textContent .= "Format Score: {$analysis->format_score}/100\n";
            $textContent .= "Keyword Score: {$analysis->keyword_score}/100\n\n";

            if ($analysis->recommendations) {
                $textContent .= "RECOMMENDATIONS:\n";
                foreach ($analysis->recommendations as $i => $recommendation) {
                    $textContent .= ($i + 1) . ". {$recommendation}\n";
                }
            }
        }

        $filename = "resume_{$resume->id}_" . time() . '.txt';
        $filePath = "exports/txt/{$filename}";

        Storage::put($filePath, $textContent);

        return [
            'success' => true,
            'file_path' => $filePath,
            'download_url' => Storage::url($filePath),
            'format' => 'txt',
        ];
    }

    /**
     * Export optimized for LinkedIn import
     */
    private function exportToLinkedIn(Resume $resume, string $content, $analysis, array $options): array
    {
        $extractedData = $this->extractStructuredData($content);

        $linkedInData = [
            'profile' => [
                'headline' => $extractedData['summary'] ?? '',
                'summary' => $extractedData['objective'] ?? $extractedData['summary'] ?? '',
            ],
            'experience' => $this->formatExperienceForLinkedIn($extractedData['experience'] ?? []),
            'education' => $this->formatEducationForLinkedIn($extractedData['education'] ?? []),
            'skills' => $this->extractSkillsForLinkedIn($extractedData['skills'] ?? []),
            'certifications' => $extractedData['certifications'] ?? [],
        ];

        $jsonContent = json_encode($linkedInData, JSON_PRETTY_PRINT);

        $filename = "linkedin_import_{$resume->id}_" . time() . '.json';
        $filePath = "exports/linkedin/{$filename}";

        Storage::put($filePath, $jsonContent);

        return [
            'success' => true,
            'file_path' => $filePath,
            'download_url' => Storage::url($filePath),
            'format' => 'linkedin',
            'data' => $linkedInData,
        ];
    }

    /**
     * Export ATS-friendly version
     */
    private function exportATSFriendly(Resume $resume, string $content, $analysis, array $options): array
    {
        // Create ATS-optimized version
        $atsContent = $this->optimizeForATS($content, $analysis);

        // Generate both TXT and DOCX versions
        $txtResult = $this->exportToText($resume, $atsContent, null, ['ats_optimized' => true]);
        $docxResult = $this->exportToDocx($resume, $atsContent, null, ['ats_optimized' => true]);

        return [
            'success' => true,
            'formats' => [
                'txt' => $txtResult,
                'docx' => $docxResult,
            ],
            'optimization_applied' => true,
            'recommendations' => $this->getATSOptimizationTips($analysis),
        ];
    }

    // Helper methods

    private function parseResumeContent(string $content): array
    {
        $sections = [];
        $lines = explode("\n", $content);
        $currentSection = 'content';
        $sectionContent = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Detect section headers
            if ($this->isSectionHeader($line)) {
                if (!empty($sectionContent)) {
                    $sections[$currentSection] = implode("\n", $sectionContent);
                }
                $currentSection = strtolower($line);
                $sectionContent = [];
            } else {
                $sectionContent[] = $line;
            }
        }

        if (!empty($sectionContent)) {
            $sections[$currentSection] = implode("\n", $sectionContent);
        }

        return $sections;
    }

    private function isSectionHeader(string $line): bool
    {
        $headers = [
            'experience', 'education', 'skills', 'summary', 'objective',
            'certifications', 'achievements', 'projects', 'contact'
        ];

        foreach ($headers as $header) {
            if (stripos($line, $header) !== false && strlen($line) < 50) {
                return true;
            }
        }

        return false;
    }

    private function extractStructuredData(string $content): array
    {
        // This would implement more sophisticated parsing
        // For now, return basic structure
        return [
            'contact' => $this->extractContactInfo($content),
            'summary' => $this->extractSummary($content),
            'experience' => $this->extractExperience($content),
            'education' => $this->extractEducation($content),
            'skills' => $this->extractSkills($content),
        ];
    }

    private function extractContactInfo(string $content): array
    {
        $contact = [];

        // Email
        if (preg_match('/([a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $content, $matches)) {
            $contact['email'] = $matches[1];
        }

        // Phone
        if (preg_match('/(\+?1?[-.\s]?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4})/', $content, $matches)) {
            $contact['phone'] = $matches[1];
        }

        return $contact;
    }

    private function extractSummary(string $content): string
    {
        // Look for summary/objective section
        if (preg_match('/(?:summary|objective)[:\s]*([^.]+\.(?:[^.]+\.)*)/i', $content, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function extractExperience(string $content): array
    {
        // Basic experience extraction - would be more sophisticated in production
        return [];
    }

    private function extractEducation(string $content): array
    {
        // Basic education extraction
        return [];
    }

    private function extractSkills(string $content): array
    {
        // Basic skills extraction
        return [];
    }

    private function convertHtmlToPdf(string $html, int $resumeId, array $options): string
    {
        // Try different PDF generation methods
        $methods = ['wkhtmltopdf', 'puppeteer', 'dompdf'];

        foreach ($methods as $method) {
            try {
                return $this->{"generatePdfWith" . ucfirst($method)}($html, $resumeId, $options);
            } catch (Exception $e) {
                continue;
            }
        }

        throw new Exception('Unable to generate PDF with any available method');
    }

    private function generatePdfWithWkhtmltopdf(string $html, int $resumeId, array $options): string
    {
        if (!$this->commandExists('wkhtmltopdf')) {
            throw new Exception('wkhtmltopdf not available');
        }

        $filename = "resume_{$resumeId}_" . time() . '.pdf';
        $filePath = "exports/pdf/{$filename}";
        $fullPath = storage_path("app/{$filePath}");

        // Create directory if needed
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Save HTML to temp file
        $htmlFile = tempnam(sys_get_temp_dir(), 'resume_html_');
        file_put_contents($htmlFile, $html);

        // Generate PDF
        $command = "wkhtmltopdf --page-size A4 --margin-top 10mm --margin-bottom 10mm --margin-left 10mm --margin-right 10mm '{$htmlFile}' '{$fullPath}'";
        exec($command, $output, $returnCode);

        unlink($htmlFile);

        if ($returnCode !== 0) {
            throw new Exception('PDF generation failed');
        }

        return $filePath;
    }

    private function generatePdfWithDompdf(string $html, int $resumeId, array $options): string
    {
        // Fallback using DomPDF (would require composer package)
        throw new Exception('DomPDF not implemented');
    }

    private function commandExists(string $command): bool
    {
        $which = shell_exec("which {$command} 2>/dev/null");
        return !empty($which);
    }

    private function formatExperienceForLinkedIn(array $experience): array
    {
        // Format experience for LinkedIn import
        return [];
    }

    private function formatEducationForLinkedIn(array $education): array
    {
        // Format education for LinkedIn import
        return [];
    }

    private function extractSkillsForLinkedIn(array $skills): array
    {
        // Extract and format skills for LinkedIn
        return [];
    }

    private function optimizeForATS(string $content, $analysis): string
    {
        // Apply ATS optimization rules
        $optimized = $content;

        // Remove special characters that might confuse ATS
        $optimized = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $optimized);

        // Ensure proper formatting
        $optimized = preg_replace('/\s+/', ' ', $optimized);
        $optimized = str_replace(["\r\n", "\r"], "\n", $optimized);

        return trim($optimized);
    }

    private function getATSOptimizationTips($analysis): array
    {
        return [
            'Use standard fonts (Arial, Calibri, Times New Roman)',
            'Avoid graphics, images, or complex formatting',
            'Use standard section headings',
            'Include relevant keywords from job descriptions',
            'Save in both .docx and .pdf formats',
        ];
    }
}