<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = [
        'user_id',
        'likeable_id',
        'likeable_type',
    ];

    /**
     * Get the user that owns the like.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the owning likeable model (Post, Story, etc).
     */
    public function likeable()
    {
        return $this->morphTo();
    }
}
