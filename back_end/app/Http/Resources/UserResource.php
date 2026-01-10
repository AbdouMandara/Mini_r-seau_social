<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'photo_profil' => $this->photo_profil,
            'bio' => $this->bio,
            'is_admin' => $this->is_admin,
            'is_blocked' => $this->is_blocked,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'current_title' => $this->current_title,
            'badges' => $this->badges,
        ];
    }
}
