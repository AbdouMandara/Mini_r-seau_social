<?php

namespace App\Services;

use App\Models\User;
use App\Models\Badge;
use App\Models\Notification;
use App\Events\NotificationSent;

class BadgeService
{
    /**
     * Check and award badges to a user based on their stats.
     *
     * @param User $user
     * @return void
     */
    public static function checkBadges(User $user)
    {
        // Load existing badges to avoid duplicates
        $userBadgesIds = $user->badges()->pluck('badges.id_badge')->toArray();

        // Fetch all badges that have criteria
        $availableBadges = Badge::whereNotNull('criteria_type')->get();

        $newBadgeAwarded = false;

        foreach ($availableBadges as $badge) {
            // Skip if user already has this badge
            if (in_array($badge->id_badge, $userBadgesIds)) {
                continue;
            }

            $meetsCriteria = false;

            switch ($badge->criteria_type) {
                case 'posts_count':
                    $count = $user->posts()->count();
                    if ($count >= $badge->criteria_value) {
                        $meetsCriteria = true;
                    }
                    break;

                case 'likes_received_count':
                    // Count likes received on all user's posts
                    $count = \App\Models\Like::whereHas('post', function($q) use ($user) {
                        $q->where('id_user', $user->id);
                    })->count();
                    
                    if ($count >= $badge->criteria_value) {
                        $meetsCriteria = true;
                    }
                    break;
            }

            if ($meetsCriteria) {
                // Award the badge
                $user->badges()->attach($badge->id_badge);
                
                // Update user title
                $user->current_title = $badge->name;
                $user->save();

                // Create notification
                $notification = Notification::create([
                    'id_user_target' => $user->id,
                    'id_user_author' => $user->id, // System notifications often use the target as author or a system ID
                    'id_badge' => $badge->id_badge,
                    'type' => 'badge',
                    'is_read' => false
                ]);

                // Broadcast notification
                broadcast(new NotificationSent($notification))->toOthers();
                
                $newBadgeAwarded = true;
            }
        }
    }
}
