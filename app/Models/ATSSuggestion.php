<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ATSSuggestion extends Model
{
    use HasFactory;

    protected $table = 'ats_suggestions';

    protected $fillable = [
        'resume_id',
        'section_id',
        'suggestion_type',
        'priority',
        'title',
        'description',
        'original_text',
        'suggested_text',
        'ats_impact',
        'reason',
        'status',
        'applied_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'ats_impact' => 'integer',
        'applied_at' => 'datetime',
    ];

    /**
     * Valid suggestion types
     */
    public const SUGGESTION_TYPES = [
        'keyword' => 'Keyword Optimization',
        'format' => 'Format Improvement',
        'content' => 'Content Enhancement',
        'structure' => 'Structure Optimization',
        'achievement' => 'Achievement Quantification',
        'grammar' => 'Grammar & Language',
        'ats_compatibility' => 'ATS Compatibility',
    ];

    /**
     * Valid priority levels
     */
    public const PRIORITIES = [
        'critical' => 'Critical',
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
    ];

    /**
     * Valid status values
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'applied' => 'Applied',
        'dismissed' => 'Dismissed',
        'expired' => 'Expired',
    ];

    /**
     * Get the resume this suggestion belongs to
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * Get the section this suggestion belongs to
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ResumeSection::class, 'section_id');
    }

    /**
     * Get the suggestion type display name
     */
    public function getSuggestionTypeNameAttribute(): string
    {
        return self::SUGGESTION_TYPES[$this->suggestion_type] ?? ucfirst($this->suggestion_type);
    }

    /**
     * Get the priority display name
     */
    public function getPriorityNameAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst($this->priority);
    }

    /**
     * Get the status display name
     */
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Check if this suggestion is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if this suggestion has been applied
     */
    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }

    /**
     * Mark this suggestion as applied
     */
    public function markAsApplied(): void
    {
        $this->update([
            'status' => 'applied',
            'applied_at' => now(),
        ]);
    }

    /**
     * Mark this suggestion as dismissed
     */
    public function markAsDismissed(): void
    {
        $this->update(['status' => 'dismissed']);
    }

    /**
     * Scope to get pending suggestions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get suggestions by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get critical suggestions
     */
    public function scopeCritical($query)
    {
        return $query->where('priority', 'critical');
    }

    /**
     * Scope to order by priority and ATS impact
     */
    public function scopeOrderedByImportance($query)
    {
        return $query->orderByRaw("
            CASE priority 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END
        ")->orderByDesc('ats_impact');
    }
}