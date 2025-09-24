<?php

namespace App\Services;

use App\Models\Resume;
use Illuminate\Support\Facades\Storage;
use Exception;

class FileProcessingService
{
    public function parseResume(Resume $resume): array
    {
        if (!Storage::exists($resume->storage_path)) {
            throw new Exception('Resume file not found');
        }

        $filePath = Storage::path($resume->storage_path);
        $fileType = $resume->file_type;

        $extractedText = match ($fileType) {
            'application/pdf' => $this->extractFromPdf($filePath),
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => $this->extractFromWord($filePath),
            'text/plain' => $this->extractFromText($filePath),
            default => throw new Exception("Unsupported file type: {$fileType}")
        };

        $sections = $this->extractSections($extractedText);
        $entities = $this->extractEntities($extractedText);

        return [
            'raw_text' => $extractedText,
            'sections' => $sections,
            'entities' => $entities,
            'metadata' => [
                'word_count' => str_word_count($extractedText),
                'character_count' => strlen($extractedText),
                'parsed_at' => now()->toISOString(),
            ],
        ];
    }

    private function extractFromPdf(string $filePath): string
    {
        try {
            // Try using pdftotext if available (best option)
            if ($this->commandExists('pdftotext')) {
                $output = shell_exec("pdftotext '{$filePath}' -");
                if ($output !== null && trim($output) !== '') {
                    return $this->cleanExtractedText(trim($output));
                }
            }

            // Try Python based extraction if available
            if ($this->commandExists('python3')) {
                $pythonScript = $this->createTempPythonScript();
                $output = shell_exec("python3 {$pythonScript} '{$filePath}' 2>/dev/null");
                if ($output !== null && trim($output) !== '') {
                    return $this->cleanExtractedText(trim($output));
                }
            }

            // Fallback: basic PDF text extraction using regex
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new Exception('Could not read PDF file');
            }

            // Improved regex-based text extraction
            $text = '';

            // Extract text objects
            if (preg_match_all('/\(([^)]+)\)/', $content, $matches)) {
                $text .= implode(' ', $matches[1]) . ' ';
            }

            // Extract text streams
            if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $matches)) {
                foreach ($matches[1] as $stream) {
                    // Basic decompression attempt
                    $decoded = @gzuncompress($stream);
                    if ($decoded !== false) {
                        $text .= $decoded . ' ';
                    }
                }
            }

            if (empty(trim($text))) {
                return 'Unable to extract text from PDF. Please try a different format or ensure the PDF contains selectable text.';
            }

            return $this->cleanExtractedText($text);

        } catch (Exception $e) {
            return 'Error extracting text from PDF: ' . $e->getMessage();
        }
    }

    private function createTempPythonScript(): string
    {
        $script = <<<PYTHON
import sys
try:
    import PyPDF2
    with open(sys.argv[1], 'rb') as file:
        reader = PyPDF2.PdfReader(file)
        text = ''
        for page in reader.pages:
            text += page.extract_text()
        print(text)
except ImportError:
    try:
        import fitz  # PyMuPDF
        doc = fitz.open(sys.argv[1])
        text = ''
        for page in doc:
            text += page.get_text()
        print(text)
    except ImportError:
        print('No PDF library available')
except Exception as e:
    print(f'Error: {e}')
PYTHON;

        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_extract_') . '.py';
        file_put_contents($tempFile, $script);
        return $tempFile;
    }

    private function extractFromWord(string $filePath): string
    {
        try {
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

            if ($fileExtension === 'docx') {
                return $this->extractFromDocx($filePath);
            } else {
                return $this->extractFromDoc($filePath);
            }
        } catch (Exception $e) {
            return 'Error extracting text from Word document: ' . $e->getMessage();
        }
    }

    private function extractFromDocx(string $filePath): string
    {
        // Basic DOCX extraction (in production, use PhpOffice/PhpWord)
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                throw new Exception('Could not open DOCX file');
            }

            $content = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($content === false) {
                throw new Exception('Could not extract content from DOCX');
            }

            // Remove XML tags and decode entities
            $text = strip_tags($content);
            $text = html_entity_decode($text);

            return $this->cleanExtractedText($text);

        } catch (Exception $e) {
            return 'Error extracting text from DOCX: ' . $e->getMessage();
        }
    }

    private function extractFromDoc(string $filePath): string
    {
        // Basic DOC extraction (limited support)
        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new Exception('Could not read DOC file');
            }

            // Very basic extraction for .doc files
            $text = preg_replace('/[^\x20-\x7E]/', ' ', $content);
            $text = preg_replace('/\s+/', ' ', $text);

            return $this->cleanExtractedText($text);

        } catch (Exception $e) {
            return 'Error extracting text from DOC file. Please convert to DOCX or PDF.';
        }
    }

    private function extractFromText(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception('Could not read text file');
        }

        return $this->cleanExtractedText($content);
    }

    private function cleanExtractedText(string $text): string
    {
        // Clean up extracted text
        $text = preg_replace('/\s+/', ' ', $text); // Multiple spaces to single
        $text = preg_replace('/\n+/', "\n", $text); // Multiple newlines to single
        $text = trim($text);

        return $text;
    }

    private function extractSections(string $text): array
    {
        $sections = [
            'contact' => $this->extractContactInfo($text),
            'summary' => $this->extractSummary($text),
            'experience' => $this->extractExperience($text),
            'education' => $this->extractEducation($text),
            'skills' => $this->extractSkills($text),
            'projects' => $this->extractProjects($text),
            'certifications' => $this->extractCertifications($text),
        ];

        return array_filter($sections);
    }

    private function extractEntities(string $text): array
    {
        return [
            'emails' => $this->extractEmails($text),
            'phones' => $this->extractPhones($text),
            'urls' => $this->extractUrls($text),
            'companies' => $this->extractCompanies($text),
            'schools' => $this->extractSchools($text),
            'skills' => $this->extractSkillEntities($text),
        ];
    }

    private function extractContactInfo(string $text): array
    {
        $contact = [];

        // Extract email
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $text, $matches)) {
            $contact['email'] = $matches[0];
        }

        // Extract phone
        if (preg_match('/(?:\+?1[-.\s]?)?\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})/', $text, $matches)) {
            $contact['phone'] = $matches[0];
        }

        // Extract LinkedIn
        if (preg_match('/linkedin\.com\/in\/[\w-]+/', $text, $matches)) {
            $contact['linkedin'] = 'https://' . $matches[0];
        }

        return $contact;
    }

    private function extractSummary(string $text): ?string
    {
        $patterns = [
            '/(?:summary|profile|overview|objective)\s*:?\s*\n?(.{100,500}?)(?:\n\n|\n[A-Z]|$)/is',
            '/(?:professional\s+summary|career\s+objective)\s*:?\s*\n?(.{100,500}?)(?:\n\n|\n[A-Z]|$)/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function extractExperience(string $text): array
    {
        $experiences = [];

        // Look for experience sections
        if (preg_match('/(?:experience|employment|work\s+history)\s*:?\s*\n(.+?)(?:\n(?:education|skills|projects)|$)/is', $text, $matches)) {
            $experienceText = $matches[1];

            // Split by likely job entries (year patterns)
            $jobs = preg_split('/\n(?=\d{4}|\w+\s+\d{4})/i', $experienceText);

            foreach ($jobs as $job) {
                if (trim($job) && strlen(trim($job)) > 50) {
                    $experiences[] = trim($job);
                }
            }
        }

        return $experiences;
    }

    private function extractEducation(string $text): array
    {
        $education = [];

        if (preg_match('/(?:education|academic|qualifications)\s*:?\s*\n(.+?)(?:\n(?:experience|skills|projects)|$)/is', $text, $matches)) {
            $educationText = $matches[1];

            // Split by degree/school entries
            $schools = preg_split('/\n(?=\w+.*(?:university|college|school|institute))/i', $educationText);

            foreach ($schools as $school) {
                if (trim($school) && strlen(trim($school)) > 20) {
                    $education[] = trim($school);
                }
            }
        }

        return $education;
    }

    private function extractSkills(string $text): array
    {
        $skills = [];

        if (preg_match('/(?:skills|technologies|competencies)\s*:?\s*\n(.+?)(?:\n(?:experience|education|projects)|$)/is', $text, $matches)) {
            $skillsText = $matches[1];

            // Split by common separators
            $skillList = preg_split('/[,;•\n]/', $skillsText);

            foreach ($skillList as $skill) {
                $skill = trim($skill);
                if ($skill && strlen($skill) > 2 && strlen($skill) < 50) {
                    $skills[] = $skill;
                }
            }
        }

        return $skills;
    }

    private function extractProjects(string $text): array
    {
        $projects = [];

        if (preg_match('/(?:projects|portfolio)\s*:?\s*\n(.+?)(?:\n(?:experience|education|skills)|$)/is', $text, $matches)) {
            $projectsText = $matches[1];

            // Split by project entries
            $projectList = preg_split('/\n(?=\w+.*:)/i', $projectsText);

            foreach ($projectList as $project) {
                if (trim($project) && strlen(trim($project)) > 30) {
                    $projects[] = trim($project);
                }
            }
        }

        return $projects;
    }

    private function extractCertifications(string $text): array
    {
        $certifications = [];

        if (preg_match('/(?:certifications?|certificates?|licenses?)\s*:?\s*\n(.+?)(?:\n(?:experience|education|skills)|$)/is', $text, $matches)) {
            $certificationsText = $matches[1];

            $certList = preg_split('/\n/', $certificationsText);

            foreach ($certList as $cert) {
                $cert = trim($cert);
                if ($cert && strlen($cert) > 10) {
                    $certifications[] = $cert;
                }
            }
        }

        return $certifications;
    }

    private function extractEmails(string $text): array
    {
        preg_match_all('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $text, $matches);
        return array_unique($matches[0]);
    }

    private function extractPhones(string $text): array
    {
        preg_match_all('/(?:\+?1[-.\s]?)?\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})/', $text, $matches);
        return array_unique($matches[0]);
    }

    private function extractUrls(string $text): array
    {
        preg_match_all('/https?:\/\/[^\s]+/', $text, $matches);
        return array_unique($matches[0]);
    }

    private function extractCompanies(string $text): array
    {
        // This would be enhanced with a company database
        $commonCompanies = ['Google', 'Microsoft', 'Apple', 'Amazon', 'Facebook', 'Netflix', 'Uber', 'Airbnb'];
        $foundCompanies = [];

        foreach ($commonCompanies as $company) {
            if (stripos($text, $company) !== false) {
                $foundCompanies[] = $company;
            }
        }

        return $foundCompanies;
    }

    private function extractSchools(string $text): array
    {
        // Extract educational institutions
        preg_match_all('/\b\w+\s+(?:University|College|Institute|School)\b/i', $text, $matches);
        return array_unique($matches[0]);
    }

    private function extractSkillEntities(string $text): array
    {
        // Common programming and technical skills
        $technicalSkills = [
            'JavaScript', 'Python', 'Java', 'C++', 'PHP', 'Ruby', 'Go', 'Rust',
            'React', 'Vue', 'Angular', 'Node.js', 'Laravel', 'Django', 'Spring',
            'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'AWS', 'Azure', 'Docker',
            'Kubernetes', 'Git', 'Jenkins', 'Terraform', 'HTML', 'CSS', 'SQL'
        ];

        $foundSkills = [];

        foreach ($technicalSkills as $skill) {
            if (stripos($text, $skill) !== false) {
                $foundSkills[] = $skill;
            }
        }

        return $foundSkills;
    }

    private function commandExists(string $command): bool
    {
        $whereIsCommand = shell_exec("which $command");
        return !empty($whereIsCommand);
    }
}