<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'role',
        'profile_data',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'profile_data' => 'array',
            'preferences' => 'array',
        ];
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function activeResumes(): HasMany
    {
        return $this->resumes()->where('is_active', true);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function getInitialsAttribute(): string
    {
        $firstInitial = substr($this->first_name, 0, 1);
        $lastInitial = substr($this->last_name, 0, 1);

        return strtoupper($firstInitial . $lastInitial);
    }

    public function getResumeCountAttribute(): int
    {
        return $this->resumes()->count();
    }

    public function getActiveResumeCountAttribute(): int
    {
        return $this->activeResumes()->count();
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(UserAnalytics::class);
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserSubscription::class)->latest();
    }

    public function activeSubscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserSubscription::class)
                   ->where('status', 'active')
                   ->where('period_ends_at', '>', now());
    }

    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'causer');
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function canUploadResume(): bool
    {
        $subscription = $this->activeSubscription()->first();

        if (!$subscription) {
            return false;
        }

        return $subscription->canUploadResume();
    }

    public function getCurrentPlan(): string
    {
        $subscription = $this->activeSubscription()->first();
        return $subscription?->plan ?? 'free';
    }

    public function getSubscriptionStatus(): string
    {
        $subscription = $this->subscription;

        if (!$subscription) {
            return 'none';
        }

        if ($subscription->isExpired()) {
            return 'expired';
        }

        return $subscription->status;
    }

    public function getRemainingResumes(): int
    {
        $subscription = $this->activeSubscription()->first();
        return $subscription?->remaining_resumes ?? 0;
    }

    public function logActivity(string $description, $subject = null, array $properties = []): ActivityLog
    {
        return ActivityLog::logForUser($this, $description, $subject, $properties);
    }
}
