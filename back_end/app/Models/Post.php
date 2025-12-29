<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $primaryKey = 'id_post';

    protected $fillable = [
        'img_post',
        'description',
        'is_delete',
        'id_user',
        'allow_comments',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'id_post');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'id_post');
    }
}
