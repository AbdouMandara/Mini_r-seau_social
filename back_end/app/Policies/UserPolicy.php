<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $auth, User $user): bool
    {
        // 🔒 Un utilisateur ne peut modifier que son propre profil.
        return $auth->id === $user->id;
    }

    /**
     * Determine whether the user can manage admin tasks.
     */
    public function manage(User $auth): bool
    {
        // 🔒 Seuls les administrateurs peuvent accéder à la gestion.
        return (bool) $auth->is_admin;
    }
}
