<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;

class FeedbackPolicy
{
    /**
     * Determine whether the user can view any feedback.
     */
    public function viewAny(User $user): bool
    {
        // 🔒 Seuls les admins peuvent voir les feedbacks.
        return (bool) $user->is_admin;
    }

    /**
     * Determine whether the user can create a feedback.
     */
    public function create(User $user): bool
    {
        // 🔒 Tout utilisateur connecté peut envoyer un feedback.
        return true;
    }
}
