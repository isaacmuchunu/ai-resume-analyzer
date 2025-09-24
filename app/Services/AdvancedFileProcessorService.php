<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class AdvancedFileProcessorService extends FileProcessingService
{
    /**
     * Enhanced file processing with multiple format support
     */
    public function processAdvancedFile(UploadedFile $file): array
    {
        $startTime = microtime(true);
        $result = [
            'success' => false,
            'content' => '',
            'metadata' => [],
            'processing_time' => 0,
            'format_detected' => '',
            'confidence_score' => 0,
        ];

        try {
            // Detect file format more accurately
            $format = $this->detectFileFormat($file);
            $result['format_detected'] = $format;

            // Process based on format
            switch ($format) {
                case 'pdf':
                    $content = $this->extractFromPdfAdvanced($file->path());
                    break;
                case 'docx':
                    $content = $this->extractFromDocxAdvanced($file->path());
                    break;
                case 'doc':
                    $content = $this->extractFromDocAdvanced($file->path());
                    break;
                case 'txt':
                    $content = $this->extractFromText($file->path());
                    break;
                case 'rtf':
                    $content = $this->extractFromRtf($file->path());
                    break;
                case 'html':
                    $content = $this->extractFromHtml($file->path());
                    break;
                case 'odt':
                    $content = $this->extractFromOdt($file->path());
                    break;
                default:
                    throw new Exception("Unsupported file format: {$format}");
            }

            // Clean and validate content
            $content = $this->cleanExtractedText($content);
            $result['content'] = $content;
            $result['metadata'] = $this->extractMetadata($content, $file);
            $result['confidence_score'] = $this->calculateConfidenceScore($content, $result['metadata']);
            $result['success'] = !empty(trim($content));

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error('Advanced file processing failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
        }

        $result['processing_time'] = round((microtime(true) - $startTime) * 1000, 2);
        return $result;
    }

    /**
     * Detect file format with higher accuracy
     */
    private function detectFileFormat(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        // Read file signature
        $handle = fopen($file->path(), 'rb');
        $signature = fread($handle, 8);
        fclose($handle);

        // Detect based on file signature
        if (substr($signature, 0, 4) === '%PDF') {
            return 'pdf';
        }

        if (substr($signature, 0, 2) === 'PK') {
            // ZIP-based format
            if ($extension === 'docx' || str_contains($mimeType, 'wordprocessingml')) {
                return 'docx';
            }
            if ($extension === 'odt' || str_contains($mimeType, 'opendocument')) {
                return 'odt';
            }
        }

        if (substr($signature, 0, 8) === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            // Microsoft Office binary format
            return 'doc';
        }

        // Fallback to extension and MIME type
        return match (true) {
            $extension === 'pdf' || str_contains($mimeType, 'pdf') => 'pdf',
            $extension === 'docx' || str_contains($mimeType, 'wordprocessingml') => 'docx',
            $extension === 'doc' || str_contains($mimeType, 'msword') => 'doc',
            $extension === 'txt' || str_contains($mimeType, 'text/plain') => 'txt',
            $extension === 'rtf' || str_contains($mimeType, 'rtf') => 'rtf',
            $extension === 'html' || $extension === 'htm' || str_contains($mimeType, 'html') => 'html',
            $extension === 'odt' || str_contains($mimeType, 'opendocument') => 'odt',
            default => $extension ?: 'unknown'
        };
    }

    /**
     * Advanced PDF extraction with multiple fallbacks
     */
    private function extractFromPdfAdvanced(string $filePath): string
    {
        $methods = [
            'pdftotext' => fn() => $this->extractPdfWithPdfToText($filePath),
            'python_pymupdf' => fn() => $this->extractPdfWithPyMuPdf($filePath),
            'python_pypdf2' => fn() => $this->extractPdfWithPyPdf2($filePath),
            'basic_regex' => fn() => $this->extractPdfWithRegex($filePath),
        ];

        foreach ($methods as $method => $callback) {
            try {
                $content = $callback();
                if (!empty(trim($content)) && strlen($content) > 50) {
                    Log::info("PDF extraction successful with method: {$method}");
                    return $content;
                }
            } catch (Exception $e) {
                Log::debug("PDF extraction method {$method} failed: " . $e->getMessage());
            }
        }

        throw new Exception('Unable to extract text from PDF with any available method');
    }

    private function extractPdfWithPdfToText(string $filePath): string
    {
        if (!$this->commandExists('pdftotext')) {
            throw new Exception('pdftotext not available');
        }

        $output = shell_exec("pdftotext -layout '{$filePath}' - 2>/dev/null");
        if ($output === null || empty(trim($output))) {
            throw new Exception('pdftotext returned empty result');
        }

        return $output;
    }

    private function extractPdfWithPyMuPdf(string $filePath): string
    {
        if (!$this->commandExists('python3')) {
            throw new Exception('Python not available');
        }

        $script = $this->createPyMuPdfScript();
        $output = shell_exec("python3 {$script} '{$filePath}' 2>/dev/null");

        if ($output === null || empty(trim($output))) {
            throw new Exception('PyMuPDF extraction failed');
        }

        return $output;
    }

    private function extractPdfWithPyPdf2(string $filePath): string
    {
        if (!$this->commandExists('python3')) {
            throw new Exception('Python not available');
        }

        $script = $this->createPyPdf2Script();
        $output = shell_exec("python3 {$script} '{$filePath}' 2>/dev/null");

        if ($output === null || empty(trim($output))) {
            throw new Exception('PyPDF2 extraction failed');
        }

        return $output;
    }

    private function extractPdfWithRegex(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception('Could not read PDF file');
        }

        $text = '';

        // Extract text objects
        if (preg_match_all('/\((.*?)\)/', $content, $matches)) {
            $text .= implode(' ', $matches[1]) . ' ';
        }

        // Extract stream content
        if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if ($decoded !== false) {
                    $text .= $decoded . ' ';
                }
            }
        }

        if (empty(trim($text))) {
            throw new Exception('Regex extraction found no text');
        }

        return $text;
    }

    /**
     * Extract from RTF files
     */
    private function extractFromRtf(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception('Could not read RTF file');
        }

        // Remove RTF control codes
        $text = preg_replace('/\{\\\\[^}]*\}/', '', $content);
        $text = preg_replace('/\\\\[a-z]+\d*\s?/', '', $text);
        $text = str_replace(['{', '}'], '', $text);

        return $this->cleanExtractedText($text);
    }

    /**
     * Extract from HTML files
     */
    private function extractFromHtml(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception('Could not read HTML file');
        }

        // Remove script and style tags
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
        $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);

        // Strip HTML tags
        $text = strip_tags($content);
        $text = html_entity_decode($text);

        return $this->cleanExtractedText($text);
    }

    /**
     * Extract from ODT files
     */
    private function extractFromOdt(string $filePath): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                throw new Exception('Could not open ODT file');
            }

            $content = $zip->getFromName('content.xml');
            $zip->close();

            if ($content === false) {
                throw new Exception('Could not extract content from ODT');
            }

            // Parse XML and extract text
            $dom = new \DOMDocument();
            $dom->loadXML($content);

            $xpath = new \DOMXPath($dom);
            $textNodes = $xpath->query('//text()');

            $text = '';
            foreach ($textNodes as $node) {
                $text .= $node->nodeValue . ' ';
            }

            return $this->cleanExtractedText($text);

        } catch (Exception $e) {
            throw new Exception('ODT extraction failed: ' . $e->getMessage());
        }
    }

    /**
     * Extract metadata from content
     */
    private function extractMetadata(string $content, UploadedFile $file): array
    {
        return [
            'word_count' => str_word_count($content),
            'character_count' => strlen($content),
            'line_count' => substr_count($content, "\n") + 1,
            'paragraph_count' => count(array_filter(explode("\n\n", $content))),
            'file_size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
            'detected_encoding' => mb_detect_encoding($content),
            'has_formatting' => $this->hasFormattingIndicators($content),
            'sections_detected' => $this->detectSections($content),
        ];
    }

    /**
     * Calculate confidence score for extraction
     */
    private function calculateConfidenceScore(string $content, array $metadata): int
    {
        $score = 0;

        // Base score from content length
        if ($metadata['word_count'] > 50) $score += 30;
        if ($metadata['word_count'] > 100) $score += 20;
        if ($metadata['word_count'] > 200) $score += 20;

        // Check for common resume sections
        $sections = ['experience', 'education', 'skills', 'contact', 'summary'];
        $sectionsFound = 0;
        foreach ($sections as $section) {
            if (stripos($content, $section) !== false) {
                $sectionsFound++;
            }
        }
        $score += min($sectionsFound * 6, 30);

        return min($score, 100);
    }

    /**
     * Detect if content has formatting indicators
     */
    private function hasFormattingIndicators(string $content): bool
    {
        return preg_match('/[A-Z][A-Z\s]+/', $content) || // All caps sections
               preg_match('/^\s*[\*\-]\s/m', $content) ||  // Bullet points
               preg_match('/^\s*\d+\.\s/m', $content);     // Numbered lists
    }

    /**
     * Detect common resume sections
     */
    private function detectSections(string $content): array
    {
        $sections = [];
        $sectionPatterns = [
            'contact' => '/contact|email|phone|address/i',
            'summary' => '/summary|profile|objective/i',
            'experience' => '/experience|employment|work|career/i',
            'education' => '/education|degree|university|college/i',
            'skills' => '/skills|competencies|technologies/i',
            'certifications' => '/certification|certificate|license/i',
            'achievements' => '/achievements|awards|honors/i',
        ];

        foreach ($sectionPatterns as $section => $pattern) {
            if (preg_match($pattern, $content)) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * Create PyMuPDF script
     */
    private function createPyMuPdfScript(): string
    {
        $script = <<<PYTHON
import sys
try:
    import fitz  # PyMuPDF
    doc = fitz.open(sys.argv[1])
    text = ""
    for page in doc:
        text += page.get_text()
    print(text)
except ImportError:
    sys.exit(1)
except Exception as e:
    sys.exit(1)
PYTHON;

        $tempFile = tempnam(sys_get_temp_dir(), 'pymupdf_') . '.py';
        file_put_contents($tempFile, $script);
        return $tempFile;
    }

    /**
     * Create PyPDF2 script
     */
    private function createPyPdf2Script(): string
    {
        $script = <<<PYTHON
import sys
try:
    import PyPDF2
    with open(sys.argv[1], 'rb') as file:
        reader = PyPDF2.PdfReader(file)
        text = ""
        for page in reader.pages:
            text += page.extract_text()
        print(text)
except ImportError:
    sys.exit(1)
except Exception as e:
    sys.exit(1)
PYTHON;

        $tempFile = tempnam(sys_get_temp_dir(), 'pypdf2_') . '.py';
        file_put_contents($tempFile, $script);
        return $tempFile;
    }
}