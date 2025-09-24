<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ResumeShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'user_id',
        'shared_with_email',
        'share_token',
        'permissions',
        'expires_at',
        'message',
        'is_active',
        'access_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'is_active' => 'boolean',
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    // Accessors & Mutators
    public function getShareUrlAttribute(): string
    {
        return url("/shared/resume/{$this->share_token}");
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getCanViewAttribute(): bool
    {
        return in_array('view', $this->permissions ?? []);
    }

    public function getCanCommentAttribute(): bool
    {
        return in_array('comment', $this->permissions ?? []);
    }

    public function getCanDownloadAttribute(): bool
    {
        return in_array('download', $this->permissions ?? []);
    }

    public function getCanSuggestAttribute(): bool
    {
        return in_array('suggest', $this->permissions ?? []);
    }

    // Methods
    public function generateToken(): string
    {
        $this->share_token = Str::random(32);
        $this->save();
        return $this->share_token;
    }

    public function recordAccess(): void
    {
        $this->increment('access_count');
        $this->update(['last_accessed_at' => now()]);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function isValidAccess(): bool
    {
        return $this->is_active && !$this->is_expired;
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function extend(int $days): void
    {
        $this->update([
            'expires_at' => now()->addDays($days)
        ]);
    }

    // Static methods
    public static function createShare(Resume $resume, array $data): self
    {
        $share = self::create([
            'resume_id' => $resume->id,
            'user_id' => $resume->user_id,
            'shared_with_email' => $data['email'] ?? null,
            'share_token' => Str::random(32),
            'permissions' => $data['permissions'] ?? ['view'],
            'expires_at' => $data['expires_at'] ?? now()->addDays(7),
            'message' => $data['message'] ?? null,
            'is_active' => true,
            'access_count' => 0,
        ]);

        return $share;
    }

    public static function findByToken(string $token): ?self
    {
        return self::where('share_token', $token)->active()->first();
    }
}