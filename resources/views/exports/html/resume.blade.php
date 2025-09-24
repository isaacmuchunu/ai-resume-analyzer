@if($standalone)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $resume->original_filename }} - Resume</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-break { page-break-after: always; }
        }

        .skill-tag {
            @apply inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-2 mb-1;
        }

        .contact-link {
            @apply text-blue-600 hover:text-blue-800 underline;
        }

        .section-divider {
            @apply border-b border-gray-200 pb-4 mb-6;
        }
    </style>
</head>
<body class="bg-white">
@endif

<div class="max-w-4xl mx-auto p-8 bg-white">
    @php
        // Parse resume content into sections
        $sections = [];
        $lines = explode("\n", $content);
        $currentSection = 'header';
        $currentContent = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check if this line looks like a section header
            if (preg_match('/^(experience|education|skills|summary|objective|contact|certifications|projects|achievements)/i', $line) && strlen($line) < 50) {
                if (!empty($currentContent)) {
                    $sections[$currentSection] = implode("\n", $currentContent);
                }
                $currentSection = strtolower($line);
                $currentContent = [];
            } else {
                $currentContent[] = $line;
            }
        }

        if (!empty($currentContent)) {
            $sections[$currentSection] = implode("\n", $currentContent);
        }

        // Extract contact information
        $email = '';
        $phone = '';
        $name = '';

        if (preg_match('/([a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $content, $matches)) {
            $email = $matches[1];
        }

        if (preg_match('/(\+?1?[-.\s]?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4})/', $content, $matches)) {
            $phone = $matches[1];
        }

        $name = pathinfo($resume->original_filename, PATHINFO_FILENAME);
    @endphp

    <!-- Header Section -->
    <header class="text-center border-b-2 border-blue-600 pb-6 mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">{{ $name }}</h1>
        @if($email || $phone)
            <div class="text-gray-600 space-x-4">
                @if($email)
                    <a href="mailto:{{ $email }}" class="contact-link">{{ $email }}</a>
                @endif
                @if($phone)
                    <span class="text-gray-400">|</span>
                    <a href="tel:{{ $phone }}" class="contact-link">{{ $phone }}</a>
                @endif
            </div>
        @endif
    </header>

    <!-- Content Sections -->
    <div class="space-y-8">
        @foreach($sections as $sectionName => $sectionContent)
            @if($sectionName !== 'header' && !empty(trim($sectionContent)))
                <section class="section-divider">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 uppercase tracking-wide">
                        {{ ucfirst($sectionName) }}
                    </h2>

                    @if($sectionName === 'skills')
                        <!-- Skills Section with Tags -->
                        <div class="flex flex-wrap">
                            @php
                                $skills = preg_split('/[,\n•\-]/', $sectionContent);
                            @endphp
                            @foreach($skills as $skill)
                                @php $skill = trim($skill); @endphp
                                @if(!empty($skill) && strlen($skill) < 30)
                                    <span class="skill-tag">{{ $skill }}</span>
                                @endif
                            @endforeach
                        </div>
                    @elseif($sectionName === 'experience')
                        <!-- Experience Section with Enhanced Formatting -->
                        <div class="space-y-4">
                            @php
                                $experiences = preg_split('/\n\s*\n/', trim($sectionContent));
                            @endphp
                            @foreach($experiences as $experience)
                                @if(!empty(trim($experience)))
                                    <div class="border-l-4 border-blue-200 pl-4">
                                        <div class="prose prose-sm max-w-none">
                                            {!! nl2br(e(trim($experience))) !!}
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <!-- Default Section Formatting -->
                        <div class="prose prose-sm max-w-none text-gray-700">
                            {!! nl2br(e(trim($sectionContent))) !!}
                        </div>
                    @endif
                </section>
            @endif
        @endforeach
    </div>

    @if($analysis && ($options['include_analysis'] ?? false))
        <!-- Analysis Section -->
        <div class="print-break mt-12 border-t-2 border-gray-200 pt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Resume Analysis Report</h2>

            <!-- Score Grid -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                @php
                    $scores = [
                        'Overall' => $analysis->overall_score,
                        'ATS' => $analysis->ats_score,
                        'Content' => $analysis->content_score,
                        'Format' => $analysis->format_score,
                        'Keywords' => $analysis->keyword_score,
                    ];
                @endphp

                @foreach($scores as $label => $score)
                    <div class="text-center p-4 border border-gray-200 rounded-lg">
                        <div class="text-2xl font-bold {{ $score >= 80 ? 'text-green-600' : ($score >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $score ?? 'N/A' }}
                        </div>
                        <div class="text-sm text-gray-600 uppercase tracking-wide">{{ $label }}</div>
                    </div>
                @endforeach
            </div>

            @if($analysis->recommendations)
                <!-- Recommendations -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recommendations</h3>
                    <ul class="space-y-2">
                        @foreach($analysis->recommendations as $recommendation)
                            <li class="flex items-start">
                                <span class="text-blue-600 font-bold mr-3">•</span>
                                <span class="text-gray-700">{{ $recommendation }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($analysis->extracted_skills)
                <!-- Identified Skills -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">AI-Identified Skills</h3>
                    <div class="flex flex-wrap">
                        @foreach(collect($analysis->extracted_skills)->flatten() as $skill)
                            <span class="skill-tag bg-green-100 text-green-800">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($analysis->missing_skills)
                <!-- Missing Skills -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Suggested Skills to Add</h3>
                    <div class="flex flex-wrap">
                        @foreach($analysis->missing_skills as $skill)
                            <span class="skill-tag bg-orange-100 text-orange-800">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Footer -->
    <footer class="mt-12 pt-6 border-t border-gray-200 text-center text-sm text-gray-500 no-print">
        <p>Generated by <strong>AI Resume Analyzer</strong> on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        <p class="mt-2">
            <button onclick="window.print()" class="text-blue-600 hover:text-blue-800 underline mr-4">Print Resume</button>
            <button onclick="history.back()" class="text-blue-600 hover:text-blue-800 underline">Go Back</button>
        </p>
    </footer>
</div>

@if($standalone)
<script>
    // Auto-print functionality
    if (window.location.search.includes('print=1')) {
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    }

    // Responsive adjustments
    function adjustLayout() {
        const container = document.querySelector('.max-w-4xl');
        if (window.innerWidth < 768) {
            container.classList.remove('p-8');
            container.classList.add('p-4');
        } else {
            container.classList.remove('p-4');
            container.classList.add('p-8');
        }
    }

    window.addEventListener('resize', adjustLayout);
    adjustLayout();
</script>
</body>
</html>
@endif