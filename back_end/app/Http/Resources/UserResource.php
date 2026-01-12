<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * 🛡️ SÉCURISATION : On ne retourne que les champs nécessaires au front-end.
         * Les champs sensibles comme 'is_admin', 'is_blocked' ou 'email' sont strictement interdits.
         * La logique de sécurité (vérification des droits) se fait exclusivement côté back-end via les Policies.
         */
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'photo_profil' => $this->photo_profil,
            'bio' => $this->bio,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'current_title' => $this->current_title,
            // 'is_certified' est conservé si c'est purement cosmétique pour le front (badge de certification)
            // S'il s'agit d'un flag de sécurité, il devrait être retiré.
            'is_certified' => $this->is_certified,
            'badges' => BadgeResource::collection($this->whenLoaded('badges')),
        ];
    }
}
