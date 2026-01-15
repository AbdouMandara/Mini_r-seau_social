<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can index reports.
     */
    public function viewAny(User $user): bool
    {
        // Seuls les admins peuvent voir la liste des signalements.
        return (bool) $user->is_admin;
    }

    /**
     * Determine whether the user can update the report status.
     */
    public function update(User $user, Report $report): bool
    {
        //  Seuls les admins peuvent traiter un signalement.
        return (bool) $user->is_admin;
    }

    /**
     * Determine whether the user can create a report.
     */
    public function create(User $user): bool
    {
        // Tout utilisateur authentifié peut signaler un contenu.
        return true;
    }
}
