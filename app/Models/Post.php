<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'media',
        'media_type',
        'likes_count',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all likes for the post (polymorphic).
     */
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Check if the authenticated user has liked this post.
     */
    public function isLikedByUser($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Scope to load likes count and whether the user has liked.
     */
    public function scopeWithUserLike($query, $userId = null)
    {
        return $query->withCount('likes')
            ->when($userId, function ($q) use ($userId) {
                $q->addSelect([
                    'is_liked' => Like::selectRaw('1')
                        ->whereColumn('likeable_id', 'posts.id')
                        ->where('likeable_type', Post::class)
                        ->where('user_id', $userId)
                        ->limit(1)
                ]);
            });
    }
}
