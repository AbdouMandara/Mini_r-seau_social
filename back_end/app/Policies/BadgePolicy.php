<?php

namespace App\Policies;

use App\Models\Badge;
use App\Models\User;

class BadgePolicy
{
    /**
     * Determine whether the user can manage badges.
     */
    public function manage(User $user): bool
    {
        // 🔒 Seuls les admins peuvent créer, modifier ou supprimer des badges.
        return (bool) $user->is_admin;
    }
}
