<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resume->original_filename }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }

        body {
            font-family: {{ $options['font_family'] ?? 'Arial, sans-serif' }};
            font-size: {{ $options['font_size'] ?? '11pt' }};
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10pt;
            margin-bottom: 15pt;
        }

        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            margin: 0 0 5pt 0;
            color: #1e40af;
        }

        .contact-info {
            font-size: 10pt;
            color: #666;
        }

        .section {
            margin-bottom: 15pt;
        }

        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 2pt;
            margin-bottom: 8pt;
            text-transform: uppercase;
        }

        .content {
            text-align: justify;
        }

        .experience-item {
            margin-bottom: 10pt;
        }

        .job-title {
            font-weight: bold;
            color: #374151;
        }

        .company {
            font-style: italic;
            color: #6b7280;
        }

        .date {
            float: right;
            color: #6b7280;
            font-size: 10pt;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5pt;
        }

        .skill-tag {
            background-color: #f3f4f6;
            padding: 3pt 6pt;
            border-radius: 3pt;
            font-size: 9pt;
            color: #374151;
        }

        .analysis-section {
            margin-top: 20pt;
            border-top: 2px solid #e5e7eb;
            padding-top: 15pt;
        }

        .score-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10pt;
            margin: 10pt 0;
        }

        .score-item {
            text-align: center;
            padding: 8pt;
            border: 1px solid #e5e7eb;
            border-radius: 4pt;
        }

        .score-value {
            font-size: 14pt;
            font-weight: bold;
            color: #059669;
        }

        .score-label {
            font-size: 8pt;
            color: #6b7280;
            text-transform: uppercase;
        }

        .recommendations {
            list-style-type: none;
            padding-left: 0;
        }

        .recommendations li {
            margin-bottom: 5pt;
            padding-left: 15pt;
            position: relative;
        }

        .recommendations li:before {
            content: "•";
            color: #2563eb;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        /* Template-specific styles */
        @if($template === 'modern')
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15pt;
            margin: -20mm -20mm 15pt -20mm;
        }

        .header h1 {
            color: white;
        }

        .section-title {
            background-color: #f8fafc;
            padding: 5pt 10pt;
            margin: 0 -10pt 8pt -10pt;
            border-left: 4px solid #2563eb;
            border-bottom: none;
        }
        @endif

        @if($template === 'classic')
        body {
            font-family: 'Times New Roman', serif;
        }

        .header {
            border-bottom: 3px double #333;
        }

        .section-title {
            font-variant: small-caps;
            letter-spacing: 1pt;
        }
        @endif

        @if($template === 'minimal')
        .header {
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .section-title {
            border-bottom: none;
            font-weight: 600;
            color: #374151;
        }
        @endif
    </style>
</head>
<body>
    @php
        $sections = collect(explode("\n\n", $content))->map(function($section) {
            $lines = explode("\n", trim($section));
            $title = array_shift($lines);
            return [
                'title' => $title,
                'content' => implode("\n", $lines)
            ];
        })->filter(function($section) {
            return !empty(trim($section['content']));
        });

        $contactInfo = '';
        if (preg_match_all('/([a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})|(\+?1?[-.\s]?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4})/', $content, $matches)) {
            $contactInfo = implode(' | ', array_filter(array_unique(array_merge($matches[1], $matches[2]))));
        }
    @endphp

    <div class="header">
        <h1>{{ pathinfo($resume->original_filename, PATHINFO_FILENAME) }}</h1>
        @if($contactInfo)
            <div class="contact-info">{{ $contactInfo }}</div>
        @endif
    </div>

    <div class="content">
        @foreach($sections as $section)
            <div class="section">
                @if(!empty(trim($section['title'])) && strlen($section['title']) < 50)
                    <div class="section-title">{{ $section['title'] }}</div>
                @endif
                <div>{!! nl2br(e($section['content'])) !!}</div>
            </div>
        @endforeach
    </div>

    @if($analysis && ($options['include_analysis'] ?? false))
        <div class="analysis-section">
            <div class="section-title">Resume Analysis Report</div>

            <div class="score-grid">
                <div class="score-item">
                    <div class="score-value">{{ $analysis->overall_score ?? 'N/A' }}</div>
                    <div class="score-label">Overall</div>
                </div>
                <div class="score-item">
                    <div class="score-value">{{ $analysis->ats_score ?? 'N/A' }}</div>
                    <div class="score-label">ATS</div>
                </div>
                <div class="score-item">
                    <div class="score-value">{{ $analysis->content_score ?? 'N/A' }}</div>
                    <div class="score-label">Content</div>
                </div>
                <div class="score-item">
                    <div class="score-value">{{ $analysis->format_score ?? 'N/A' }}</div>
                    <div class="score-label">Format</div>
                </div>
                <div class="score-item">
                    <div class="score-value">{{ $analysis->keyword_score ?? 'N/A' }}</div>
                    <div class="score-label">Keywords</div>
                </div>
            </div>

            @if($analysis->recommendations)
                <div class="section">
                    <div class="section-title">Recommendations</div>
                    <ul class="recommendations">
                        @foreach($analysis->recommendations as $recommendation)
                            <li>{{ $recommendation }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($analysis->extracted_skills)
                <div class="section">
                    <div class="section-title">Identified Skills</div>
                    <div class="skills-list">
                        @foreach(collect($analysis->extracted_skills)->flatten() as $skill)
                            <span class="skill-tag">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div style="position: fixed; bottom: 10mm; right: 10mm; font-size: 8pt; color: #9ca3af;">
        Generated by AI Resume Analyzer on {{ now()->format('M j, Y') }}
    </div>
</body>
</html>