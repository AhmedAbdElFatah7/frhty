<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform_id',
        'title',
        'description',
        'image',
        'start_date',
        'end_date',
        'is_active',
        'max_attempts',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the celebrity who created this contest.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the platform this contest is for.
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * Get the questions for this contest.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    /**
     * Get the attempts for this contest.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ContestAttempt::class);
    }

    /**
     * Get the terms/conditions for this contest.
     */
    public function terms(): HasMany
    {
        return $this->hasMany(ContestTerm::class)->orderBy('order');
    }

    /**
     * Check if contest is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active
            && now()->between($this->start_date, $this->end_date);
    }

    /**
     * Check if user can attempt this contest.
     */
    public function canUserAttempt(User $user): bool
    {
        $attemptsCount = $this->attempts()
            ->where('user_id', $user->id)
            ->count();

        return $attemptsCount < $this->max_attempts;
    }
}
