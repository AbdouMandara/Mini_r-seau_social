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
        \Illuminate\Support\Facades\Log::info('Checking badges for user: ' . $user->nom);
        // Load existing badges to avoid duplicates
        $userBadgesIds = $user->badges()->pluck('badges.id_badge')->toArray();

        // Fetch all badges that have criteria
        $availableBadges = Badge::whereNotNull('criteria_type')->get();
        \Illuminate\Support\Facades\Log::info('Available badges with criteria: ' . $availableBadges->count());

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
                    \Illuminate\Support\Facades\Log::info("User {$user->nom} posts count: {$count} | Criteria: {$badge->criteria_value}");
                    if ($count >= $badge->criteria_value) {
                        $meetsCriteria = true;
                    }
                    break;

                case 'likes_received_count':
                    // Count likes received on all user's posts
                    $count = \App\Models\Like::whereHas('post', function($q) use ($user) {
                        $q->where('id_user', $user->id);
                    })->count();
                    \Illuminate\Support\Facades\Log::info("User {$user->nom} likes received: {$count} | Criteria: {$badge->criteria_value}");
                    
                    if ($count >= $badge->criteria_value) {
                        $meetsCriteria = true;
                    }
                    break;

                case 'followers_count':
                    $count = $user->followers()->count();
                    if ($count >= $badge->criteria_value) {
                        $meetsCriteria = true;
                    }
                    break;

                case 'following_count':
                    $count = $user->following()->count();
                    if ($count >= $badge->criteria_value) {
                        $meetsCriteria = true;
                    }
                    break;

                case 'comments_count':
                    $count = $user->comments()->count();
                    if ($count >= $badge->criteria_value) {
                        $meetsCriteria = true;
                    }
                    break;

                case 'post_reports_count':
                    $count = \App\Models\Report::where('id_reported_user', $user->id)->count();
                    if ($count >= $badge->criteria_value) {
                        $meetsCriteria = true;
                    }
                    break;

                case 'is_certified':
                    if ($user->is_certified) {
                        $meetsCriteria = true;
                    }
                    break;
            }

            if ($meetsCriteria) {
                \Illuminate\Support\Facades\Log::info("Awarding badge {$badge->name} to user {$user->nom}");
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
