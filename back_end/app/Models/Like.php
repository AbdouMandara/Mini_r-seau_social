<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Like extends Model
{
    use HasUuids;

    protected $primaryKey = 'id_like';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_user',
        'id_post',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'id_post');
    }
}
