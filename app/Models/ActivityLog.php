<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public static function logActivity(string $description, $subject = null, array $properties = []): self
    {
        return static::create([
            'log_name' => 'default',
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'causer_type' => auth()->check() ? get_class(auth()->user()) : null,
            'causer_id' => auth()->id(),
            'properties' => $properties,
        ]);
    }

    public static function logForUser(User $user, string $description, $subject = null, array $properties = []): self
    {
        return static::create([
            'log_name' => 'user_activity',
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'properties' => $properties,
        ]);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('causer_type', User::class)
                    ->where('causer_id', $user->id);
    }

    public function scopeInLog($query, string $logName)
    {
        return $query->where('log_name', $logName);
    }

    public function scopeCausedBy($query, Model $causer)
    {
        return $query->where('causer_type', get_class($causer))
                    ->where('causer_id', $causer->getKey());
    }

    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
                    ->where('subject_id', $subject->getKey());
    }

    public function getExtraProperty(string $propertyName, $defaultValue = null)
    {
        return $this->properties[$propertyName] ?? $defaultValue;
    }

    public function changes(): array
    {
        return $this->properties['attributes'] ?? [];
    }

    public function getChangesAttribute(): array
    {
        return $this->changes();
    }
}