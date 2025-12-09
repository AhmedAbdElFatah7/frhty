<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContestAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contest_id',
        'score',
        'total_questions',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who made this attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contest for this attempt.
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * Get the user answers for this attempt.
     */
    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class, 'attempt_id');
    }

    /**
     * Calculate and return the percentage score.
     */
    public function getPercentageAttribute(): float
    {
        if ($this->total_questions === 0) {
            return 0;
        }

        return round(($this->score / $this->total_questions) * 100, 2);
    }

    /**
     * Check if attempt is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
