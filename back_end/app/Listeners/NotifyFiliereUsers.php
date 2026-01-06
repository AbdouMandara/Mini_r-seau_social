<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\Notification;
use App\Events\PostCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyFiliereUsers implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PostCreated $event): void
    {
        $post = $event->post;
        $author = $post->user;

        if (!$author) {
            \Log::warning("Post {$post->id_post} has no user attached. Skipping filiere notification.");
            return;
        }

        // Find users in same filiere, same level, excluding author
        $usersToNotify = User::where('filiere', $author->filiere)
                             ->where('id', '!=', $author->id)
                             ->get();

        foreach ($usersToNotify as $user) {
            Notification::create([
                'id_user_target' => $user->id,
                'id_user_author' => $author->id,
                'id_post' => $post->id_post,
                'type' => 'post_filiere', // We need to handle this type in frontend
                'is_read' => false
            ]);
        }
    }
}
