<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'file_size',
        'file_type',
        'storage_path',
        'parsing_status',
        'analysis_status',
        'version',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'file_size' => 'integer',
        'version' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analysisResults(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }

    public function latestAnalysis(): ?AnalysisResult
    {
        return $this->analysisResults()->latest()->first();
    }

    public function isParsed(): bool
    {
        return $this->parsing_status === 'completed';
    }

    public function isAnalyzed(): bool
    {
        return $this->analysis_status === 'completed';
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('resumes.download', $this->id);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeParsed($query)
    {
        return $query->where('parsing_status', 'completed');
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('analysis_status', 'completed');
    }
}