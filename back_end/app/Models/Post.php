<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Post extends Model
{
    use HasUuids;

    protected $primaryKey = 'id_post';
    public $incrementing = false;
    protected $keyType = 'string';

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
