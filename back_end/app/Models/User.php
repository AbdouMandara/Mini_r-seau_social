<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'slug',
        'photo_profil',
        'etablissement',
        'filiere',
        'niveau',
        'bio',
        'password',
        'is_admin',
        'is_blocked',
        'last_seen_at',
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
            'is_admin' => 'boolean',
            'is_blocked' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'id_user');
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'id_user');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'id_user');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'followed_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id');
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'badge_user', 'id_user', 'id_badge')
                    ->withTimestamps();
    }

    public function isFollowing(User $user)
    {
        return $this->following()->where('followed_id', $user->id)->exists();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->slug)) {
                $user->slug = \Illuminate\Support\Str::slug($user->nom, '_');
            }
        });

        static::updating(function ($user) {
            if ($user->isDirty('nom')) {
                $user->slug = \Illuminate\Support\Str::slug($user->nom, '_');
            }
        });
    }
}
