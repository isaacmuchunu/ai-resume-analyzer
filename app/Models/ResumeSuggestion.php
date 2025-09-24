<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumeSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'user_id',
        'type',
        'original_text',
        'suggested_text',
        'context',
        'status',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


