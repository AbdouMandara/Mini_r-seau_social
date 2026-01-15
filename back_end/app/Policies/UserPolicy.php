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
        //  Un utilisateur ne peut modifier que son propre profil.
        return $auth->id === $user->id;
    }

    public function accessAdminPanel(User $auth): bool
    {
        //  Seuls les administrateurs peuvent accéder au panneau d'administration.
        return (bool) $auth->is_admin;
    }
}
