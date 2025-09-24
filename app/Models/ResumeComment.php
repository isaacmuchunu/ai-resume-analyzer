<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResumeComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'user_id',
        'commenter_email',
        'commenter_name',
        'content',
        'type',
        'section',
        'position_start',
        'position_end',
        'is_resolved',
        'parent_id',
        'status',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'position_start' => 'integer',
        'position_end' => 'integer',
    ];

    // Relationships
    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    // Scopes
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeBySection($query, string $section)
    {
        return $query->where('section', $section);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getCommenterDisplayNameAttribute(): string
    {
        return $this->user?->name ?? $this->commenter_name ?? 'Anonymous';
    }

    public function getIsReplyAttribute(): bool
    {
        return $this->parent_id !== null;
    }

    public function getHasRepliesAttribute(): bool
    {
        return $this->replies()->count() > 0;
    }

    // Methods
    public function resolve(): void
    {
        $this->update(['is_resolved' => true]);
    }

    public function unresolve(): void
    {
        $this->update(['is_resolved' => false]);
    }

    public function addReply(array $data): self
    {
        return self::create([
            'resume_id' => $this->resume_id,
            'user_id' => $data['user_id'] ?? null,
            'commenter_email' => $data['commenter_email'] ?? null,
            'commenter_name' => $data['commenter_name'] ?? null,
            'content' => $data['content'],
            'type' => 'reply',
            'parent_id' => $this->id,
            'status' => 'active',
        ]);
    }

    // Static methods
    public static function createComment(Resume $resume, array $data): self
    {
        return self::create([
            'resume_id' => $resume->id,
            'user_id' => $data['user_id'] ?? null,
            'commenter_email' => $data['commenter_email'] ?? null,
            'commenter_name' => $data['commenter_name'] ?? null,
            'content' => $data['content'],
            'type' => $data['type'] ?? 'general',
            'section' => $data['section'] ?? null,
            'position_start' => $data['position_start'] ?? null,
            'position_end' => $data['position_end'] ?? null,
            'status' => 'active',
        ]);
    }
}