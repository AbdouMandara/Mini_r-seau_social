<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Badge;
use App\Models\User;
use App\Events\PostCreated;
use App\Events\LikeToggled;

class AwardBadges
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event instanceof PostCreated) {
            $this->checkPostBadges($event->post->user);
        } elseif ($event instanceof LikeToggled) {
            // Check badges for the AUTHOR of the post that was liked
            $post = \App\Models\Post::find($event->postId);
            if ($post) {
                $this->checkLikeBadges($post->user);
            }
        }
    }

    protected function checkPostBadges(User $user)
    {
        $postCount = $user->posts()->count();
        $badges = Badge::where('criteria_type', 'posts_count')
                       ->where('criteria_value', '<=', $postCount)
                       ->get();

        foreach ($badges as $badge) {
            if (!$user->badges()->where('badge_user.id_badge', $badge->id_badge)->exists()) {
                $user->badges()->attach($badge->id_badge);
                // Notification (optional): "Bravo, vous avez reçu le badge X !"
            }
        }
    }

    protected function checkLikeBadges(User $user)
    {
        // Calculate total likes received by this user across all their posts
        // User -> hasMany(Post) -> hasMany(Like)
        $likesReceived = $user->posts()->withCount('likes')->get()->sum('likes_count');

        $badges = Badge::where('criteria_type', 'likes_received_count')
                       ->where('criteria_value', '<=', $likesReceived)
                       ->get();

        foreach ($badges as $badge) {
            if (!$user->badges()->where('badge_user.id_badge', $badge->id_badge)->exists()) {
                $user->badges()->attach($badge->id_badge);
            }
        }
    }
}
