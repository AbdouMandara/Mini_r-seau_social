<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $primaryKey = 'id_notif';

    protected $fillable = [
        'id_user_target',
        'id_user_author',
        'id_post',
        'type',
        'is_read'
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_target');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_author');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'id_post');
    }
}
