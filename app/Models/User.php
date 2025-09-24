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
        'api_key',
        'api_key_expires_at',
        'api_last_used_at',
        'api_key_regenerated_at',
        'login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_key',
        'two_factor_recovery_codes',
        'two_factor_secret',
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
            'api_key_expires_at' => 'datetime',
            'api_last_used_at' => 'datetime',
            'api_key_regenerated_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
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

    // Enhanced Authentication Methods

    /**
     * Check if user is locked due to too many failed login attempts
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Increment login attempts
     */
    public function incrementLoginAttempts(): void
    {
        $this->increment('login_attempts');

        if ($this->login_attempts >= 5) {
            $this->update([
                'locked_until' => now()->addMinutes(15),
            ]);
        }
    }

    /**
     * Reset login attempts
     */
    public function resetLoginAttempts(): void
    {
        $this->update([
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Record successful login
     */
    public function recordLogin(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Check if API key is valid
     */
    public function hasValidApiKey(): bool
    {
        return $this->api_key &&
               $this->api_key_expires_at &&
               $this->api_key_expires_at->isFuture();
    }

    /**
     * Generate a new API key
     */
    public function generateApiKey(): string
    {
        $apiKey = 'ara_' . \Illuminate\Support\Str::random(40);

        $this->update([
            'api_key' => \Illuminate\Support\Facades\Hash::make($apiKey),
            'api_key_expires_at' => now()->addYear(),
            'api_key_regenerated_at' => now(),
        ]);

        return $apiKey;
    }

    /**
     * Verify API key
     */
    public function verifyApiKey(string $apiKey): bool
    {
        return $this->hasValidApiKey() &&
               \Illuminate\Support\Facades\Hash::check($apiKey, $this->api_key);
    }

    /**
     * Get user's devices (basic implementation)
     */
    public function getDevices(): array
    {
        // This would typically involve a separate devices table
        // For now, return basic information
        return [
            [
                'id' => 1,
                'name' => 'Current Session',
                'ip_address' => $this->last_login_ip,
                'last_seen' => $this->last_login_at,
                'is_current' => true,
            ]
        ];
    }

    /**
     * Check password strength
     */
    public static function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[^A-Za-z0-9]/', $password);
    }

    /**
     * Get password strength score (0-4)
     */
    public static function getPasswordStrength(string $password): int
    {
        $score = 0;

        if (strlen($password) >= 8) $score++;
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/[0-9]/', $password)) $score++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;

        return min($score, 4);
    }
}
