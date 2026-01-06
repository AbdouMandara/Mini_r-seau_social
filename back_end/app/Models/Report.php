<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasUuids;

    protected $primaryKey = 'id_report';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_user_reporter',
        'id_post',
        'id_reported_user',
        'reason',
        'status'
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_reporter');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'id_post');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_reported_user');
    }
}
