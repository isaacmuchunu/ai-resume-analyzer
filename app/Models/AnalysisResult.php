<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'analysis_type',
        'overall_score',
        'ats_score',
        'content_score',
        'format_score',
        'keyword_score',
        'detailed_scores',
        'recommendations',
        'extracted_skills',
        'missing_skills',
        'keywords',
        'sections_analysis',
        'ai_insights',
    ];

    protected $casts = [
        'detailed_scores' => 'array',
        'recommendations' => 'array',
        'extracted_skills' => 'array',
        'missing_skills' => 'array',
        'keywords' => 'array',
        'sections_analysis' => 'array',
        'overall_score' => 'integer',
        'ats_score' => 'integer',
        'content_score' => 'integer',
        'format_score' => 'integer',
        'keyword_score' => 'integer',
    ];

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function getOverallGradeAttribute(): string
    {
        $score = $this->overall_score;

        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 45) return 'D+';
        if ($score >= 40) return 'D';

        return 'F';
    }

    public function getScoreColorAttribute(): string
    {
        $score = $this->overall_score;

        if ($score >= 80) return 'text-green-600';
        if ($score >= 60) return 'text-yellow-600';

        return 'text-red-600';
    }

    public function getAllScores(): array
    {
        return [
            'overall' => $this->overall_score,
            'ats' => $this->ats_score,
            'content' => $this->content_score,
            'format' => $this->format_score,
            'keyword' => $this->keyword_score,
        ];
    }

    public function getTopRecommendations(int $limit = 5): array
    {
        return array_slice($this->recommendations ?? [], 0, $limit);
    }

    public function getMissingSkillsCount(): int
    {
        return count($this->missing_skills ?? []);
    }

    public function getExtractedSkillsCount(): int
    {
        $skills = $this->extracted_skills ?? [];

        return collect($skills)->flatten()->count();
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('analysis_type', $type);
    }

    public function scopeHighScore($query, int $threshold = 80)
    {
        return $query->where('overall_score', '>=', $threshold);
    }
}