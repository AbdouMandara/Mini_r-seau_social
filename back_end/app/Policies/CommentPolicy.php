<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment): bool
    {
        // 🔒 Un utilisateur ne peut modifier que ses propres commentaires.
        return $user->id === $comment->id_user;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // 🔒 Un utilisateur ne peut supprimer ses propres commentaires, 
        // ou le propriétaire du post peut supprimer n'importe quel commentaire sur son post.
        return $user->id === $comment->id_user || $user->id === $comment->post->id_user || $user->is_admin;
    }
}
