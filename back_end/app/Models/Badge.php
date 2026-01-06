<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasUuids;

    protected $primaryKey = 'id_badge';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'description', 'icon', 'color', 'criteria_type', 'criteria_value'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'badge_user', 'id_badge', 'id_user')
                    ->withTimestamps();
    }
}
