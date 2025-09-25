<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOptimization extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'job_title',
        'job_description',
        'required_skills',
        'missing_skills',
        'keyword_gaps',
        'match_score',
        'optimization_data',
        'industry_keywords',
        'is_active',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'missing_skills' => 'array',
        'keyword_gaps' => 'array',
        'optimization_data' => 'array',
        'industry_keywords' => 'array',
        'match_score' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the resume this optimization belongs to
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * Get the match score percentage
     */
    public function getMatchPercentageAttribute(): float
    {
        return round($this->match_score, 1);
    }

    /**
     * Get the number of missing skills
     */
    public function getMissingSkillsCountAttribute(): int
    {
        return is_array($this->missing_skills) ? count($this->missing_skills) : 0;
    }

    /**
     * Get the number of keyword gaps
     */
    public function getKeywordGapsCountAttribute(): int
    {
        return is_array($this->keyword_gaps) ? count($this->keyword_gaps) : 0;
    }

    /**
     * Check if the match score is good (>= 70)
     */
    public function hasGoodMatch(): bool
    {
        return $this->match_score >= 70;
    }

    /**
     * Check if the match score needs improvement (< 60)
     */
    public function needsImprovement(): bool
    {
        return $this->match_score < 60;
    }

    /**
     * Get optimization suggestions from the data
     */
    public function getOptimizationSuggestions(): array
    {
        return $this->optimization_data['suggestions'] ?? [];
    }

    /**
     * Get skill recommendations
     */
    public function getSkillRecommendations(): array
    {
        return $this->optimization_data['skill_recommendations'] ?? [];
    }

    /**
     * Get keyword recommendations
     */
    public function getKeywordRecommendations(): array
    {
        return $this->optimization_data['keyword_recommendations'] ?? [];
    }

    /**
     * Scope to get active optimizations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get optimizations by match score range
     */
    public function scopeByMatchScore($query, int $min, int $max = 100)
    {
        return $query->whereBetween('match_score', [$min, $max]);
    }

    /**
     * Scope to get recent optimizations
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Deactivate this optimization
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Generate optimization summary
     */
    public function getOptimizationSummary(): array
    {
        return [
            'match_score' => $this->match_score,
            'missing_skills_count' => $this->missing_skills_count,
            'keyword_gaps_count' => $this->keyword_gaps_count,
            'has_good_match' => $this->hasGoodMatch(),
            'needs_improvement' => $this->needsImprovement(),
            'total_suggestions' => count($this->getOptimizationSuggestions()),
        ];
    }
}