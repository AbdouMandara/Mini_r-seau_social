<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * SÉCURISATION : On ne retourne que les champs nécessaires au front-end.
         * Les champs sensibles comme 'is_admin', 'is_blocked' ou 'email' sont strictement interdits.
         * La logique de sécurité (vérification des droits) se fait exclusivement côté back-end via les Policies.
         */
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'photo_profil' => $this->photo_profil,
            'bio' => $this->bio,
            'current_title' => $this->current_title,
            'is_blocked' => (bool) $this->is_blocked,
            'is_certified' => (bool) $this->is_certified,
            'role' => $this->is_admin ? 'admin' : 'user', 
            'posts_count' => $this->posts_count ?? 0,
            'followers_count' => $this->followers_count ?? 0,
            'following_count' => $this->following_count ?? 0,
            'likes_count' => $this->likes_count ?? 0,
            'comments_count' => $this->comments_count ?? 0,
            'badges' => BadgeResource::collection($this->whenLoaded('badges')),
            'is_following' => $this->is_following ?? false,
            'created_at' => $this->created_at,
        ];
    }
}
