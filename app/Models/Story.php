<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Story extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'contest_id',
        'media_path',
        'media_type',
        'caption',
        'expires_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created this story.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contest this story is related to.
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * Check if story is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }

    /**
     * Check if story is viewable (active and not expired).
     */
    public function isViewable(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Scope a query to only include active stories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include non-expired stories.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include viewable stories.
     */
    public function scopeViewable($query)
    {
        return $query->active()->notExpired();
    }

    /**
     * Get all views for this story.
     */
    public function views()
    {
        return $this->hasMany(StoryView::class);
    }

    /**
     * Get the count of views for this story.
     */
    public function viewsCount()
    {
        return $this->views()->count();
    }

    /**
     * Check if a user has viewed this story.
     */
    public function isViewedBy($userId)
    {
        return $this->views()->where('user_id', $userId)->exists();
    }

    /**
     * Record a view for this story by a user.
     */
    public function recordView($userId)
    {
        return $this->views()->firstOrCreate([
            'user_id' => $userId,
        ], [
            'viewed_at' => now(),
        ]);
    }
}
