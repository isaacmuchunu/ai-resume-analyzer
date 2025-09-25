<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResumeSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'section_type',
        'title',
        'content',
        'ats_score',
        'order_index',
        'metadata',
    ];

    protected $casts = [
        'content' => 'array',
        'metadata' => 'array',
        'ats_score' => 'integer',
        'order_index' => 'integer',
    ];

    /**
     * Valid section types
     */
    public const SECTION_TYPES = [
        'contact' => 'Contact Information',
        'summary' => 'Professional Summary',
        'experience' => 'Work Experience',
        'education' => 'Education',
        'skills' => 'Skills',
        'projects' => 'Projects',
        'certifications' => 'Certifications',
        'achievements' => 'Achievements',
        'languages' => 'Languages',
        'volunteer' => 'Volunteer Experience',
    ];

    /**
     * Get the resume that owns this section
     */
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * Get the ATS suggestions for this section
     */
    public function atsSuggestions(): HasMany
    {
        return $this->hasMany(ATSSuggestion::class, 'section_id');
    }

    /**
     * Get pending ATS suggestions for this section
     */
    public function pendingSuggestions(): HasMany
    {
        return $this->hasMany(ATSSuggestion::class, 'section_id')
            ->where('status', 'pending');
    }

    /**
     * Get the section type display name
     */
    public function getSectionTypeNameAttribute(): string
    {
        return self::SECTION_TYPES[$this->section_type] ?? ucfirst($this->section_type);
    }

    /**
     * Check if this section has critical ATS issues
     */
    public function hasCriticalIssues(): bool
    {
        return $this->atsSuggestions()
            ->where('priority', 'critical')
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Get the number of pending suggestions
     */
    public function getPendingSuggestionsCountAttribute(): int
    {
        return $this->pendingSuggestions()->count();
    }

    /**
     * Scope to order sections by their order_index
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    /**
     * Scope to get sections by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('section_type', $type);
    }
}