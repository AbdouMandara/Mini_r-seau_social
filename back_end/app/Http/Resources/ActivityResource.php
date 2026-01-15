<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_activity' => $this->id_activity,
            'user' => new UserResource($this->whenLoaded('user')),
            'action' => $this->action,
            'details' => $this->details,
            'created_at' => $this->created_at,
        ];
    }
}
