<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LikeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_like' => $this->id_like,
            'id_user' => $this->id_user,
            'id_post' => $this->id_post,
            'created_at' => $this->created_at,
        ];
    }
}
