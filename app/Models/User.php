<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'user_name',
        'phone',
        'password',
        'role',
        'image',
        'gender',
        'verified',
        'completed',
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
            'password' => 'hashed',
        ];
    }

    /**
     * Get the platforms for the user (NEW).
     */
    public function platforms()
    {
        return $this->belongsToMany(Platform::class, 'user_platforms')
            ->withPivot('account_url', 'followers_count')
            ->withTimestamps();
    }

    /**
     * Get users that this user is following.
     */
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    /**
     * Get users that are following this user.
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    /**
     * Check if this user is following another user.
     */
    public function isFollowing($userId)
    {
        return $this->following()->where('following_id', $userId)->exists();
    }

    /**
     * Check if this user is followed by another user.
     */
    public function isFollowedBy($userId)
    {
        return $this->followers()->where('follower_id', $userId)->exists();
    }

    /**
     * Get followers count.
     */
    public function followersCount()
    {
        return $this->followers()->count();
    }

    /**
     * Get following count.
     */
    public function followingCount()
    {
        return $this->following()->count();
    }

    /**
     * Get the stories created by this user.
     */
    public function stories()
    {
        return $this->hasMany(Story::class);
    }
}
