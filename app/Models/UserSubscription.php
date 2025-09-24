<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan',
        'status',
        'resumes_limit',
        'resumes_used',
        'period_starts_at',
        'period_ends_at',
        'cancelled_at',
        'features',
        'metadata',
    ];

    protected $casts = [
        'period_starts_at' => 'date',
        'period_ends_at' => 'date',
        'cancelled_at' => 'datetime',
        'features' => 'array',
        'metadata' => 'array',
        'resumes_limit' => 'integer',
        'resumes_used' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->period_ends_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->period_ends_at->isPast();
    }

    public function canUploadResume(): bool
    {
        return $this->isActive() &&
               ($this->resumes_limit === -1 || $this->resumes_used < $this->resumes_limit);
    }

    public function getRemainingResumesAttribute(): int
    {
        if ($this->resumes_limit === -1) {
            return -1; // unlimited
        }

        return max(0, $this->resumes_limit - $this->resumes_used);
    }

    public function getUsagePercentageAttribute(): float
    {
        if ($this->resumes_limit === -1 || $this->resumes_limit === 0) {
            return 0;
        }

        return min(100, ($this->resumes_used / $this->resumes_limit) * 100);
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, now()->diffInDays($this->period_ends_at, false));
    }

    public function incrementUsage(int $count = 1): void
    {
        $this->increment('resumes_used', $count);
    }

    public function resetUsage(): void
    {
        $this->update(['resumes_used' => 0]);
    }

    public function renew(Carbon $newEndDate = null): void
    {
        $this->update([
            'period_starts_at' => now(),
            'period_ends_at' => $newEndDate ?? now()->addMonth(),
            'status' => 'active',
            'cancelled_at' => null,
        ]);

        $this->resetUsage();
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public static function getPlanLimits(string $plan): array
    {
        return match($plan) {
            'free' => [
                'resumes_limit' => 5,
                'features' => ['basic_analysis', 'email_support']
            ],
            'basic' => [
                'resumes_limit' => 25,
                'features' => ['advanced_analysis', 'email_support', 'export_pdf']
            ],
            'pro' => [
                'resumes_limit' => 100,
                'features' => ['comprehensive_analysis', 'priority_support', 'custom_templates', 'api_access']
            ],
            'enterprise' => [
                'resumes_limit' => -1, // unlimited
                'features' => ['all_features', 'dedicated_support', 'white_label', 'sso']
            ],
            default => [
                'resumes_limit' => 5,
                'features' => ['basic_analysis']
            ]
        };
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        return in_array($feature, $features) || in_array('all_features', $features);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('period_ends_at', '>', now());
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
                    ->where('period_ends_at', '<=', now()->addDays($days))
                    ->where('period_ends_at', '>', now());
    }
}