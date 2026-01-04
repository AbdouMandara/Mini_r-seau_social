<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Activity extends Model
{
    use HasUuids;

    protected $primaryKey = 'id_activity';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_user',
        'action',
        'details'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Helper to log an activity
     */
    public static function log($userId, $action, $details = null)
    {
        return self::create([
            'id_user' => $userId,
            'action' => $action,
            'details' => $details
        ]);
    }
}
