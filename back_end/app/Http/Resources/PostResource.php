<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    //Rôle des Ressources :  formatage des réponses JSON, abstraction de ce qui est retourné à l’API.

    public function toArray(Request $request): array
    {
        return [
            'id_post' => $this->id_post,
            'description' => $this->description,
            'img_post' => $this->img_post,
            'tag' => $this->tag,
            'allow_comments' => $this->allow_comments,
            'is_delete' => $this->is_delete,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'likes' => LikeResource::collection($this->whenLoaded('likes')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'likes_count' => $this->whenCounted('likes'),
            'comments_count' => $this->whenCounted('comments'),
        ];
    }
}
