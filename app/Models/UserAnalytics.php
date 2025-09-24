<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'resumes_uploaded',
        'analyses_completed',
        'downloads_count',
        'page_views',
        'session_duration',
        'actions_taken',
    ];

    protected $casts = [
        'date' => 'date',
        'resumes_uploaded' => 'integer',
        'analyses_completed' => 'integer',
        'downloads_count' => 'integer',
        'page_views' => 'integer',
        'session_duration' => 'integer',
        'actions_taken' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function incrementForUser(int $userId, string $metric, int $value = 1): void
    {
        $analytics = static::firstOrCreate([
            'user_id' => $userId,
            'date' => today(),
        ]);

        $analytics->increment($metric, $value);
    }

    public static function getWeeklyStats(int $userId): array
    {
        $weeklyStats = static::where('user_id', $userId)
            ->where('date', '>=', now()->subDays(7))
            ->get();

        return [
            'total_resumes' => $weeklyStats->sum('resumes_uploaded'),
            'total_analyses' => $weeklyStats->sum('analyses_completed'),
            'total_downloads' => $weeklyStats->sum('downloads_count'),
            'total_sessions' => $weeklyStats->sum('page_views'),
            'avg_session_duration' => $weeklyStats->avg('session_duration'),
            'daily_breakdown' => $weeklyStats->map(function ($stat) {
                return [
                    'date' => $stat->date->format('Y-m-d'),
                    'resumes' => $stat->resumes_uploaded,
                    'analyses' => $stat->analyses_completed,
                    'downloads' => $stat->downloads_count,
                ];
            })->toArray(),
        ];
    }

    public function getFormattedSessionDurationAttribute(): string
    {
        $duration = $this->session_duration;

        if ($duration < 60) {
            return $duration . 's';
        } elseif ($duration < 3600) {
            return round($duration / 60) . 'm';
        } else {
            return round($duration / 3600, 1) . 'h';
        }
    }
}