<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Feedback extends Model
{
    use HasUuids;

    protected $table = 'feedbacks';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['user_id', 'rating', 'comment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
