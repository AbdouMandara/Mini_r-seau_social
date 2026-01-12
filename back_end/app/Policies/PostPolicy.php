<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        // 🔒 Un utilisateur ne peut modifier que ses propres posts.
        return $user->id === $post->id_user;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        // 🔒 Un utilisateur ne peut supprimer que ses propres posts.
        // Les admins pourraient avoir un droit de suppression global (à ajouter si besoin).
        return $user->id === $post->id_user || $user->is_admin;
    }

    /**
     * Determine whether the user can comment on the post.
     */
    public function comment(User $user, Post $post): bool
    {
        // 🔒 On vérifie si les commentaires sont autorisés sur ce post.
        return (bool) $post->allow_comments;
    }
}
