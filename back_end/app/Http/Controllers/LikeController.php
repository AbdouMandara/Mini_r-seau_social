<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Events\LikeToggled;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(Request $request, Post $post)
    {
        $like = Like::where('id_user', $request->user()->id)
            ->where('id_post', $post->id_post)
            ->first();

        if ($like) {
            $like->delete();
            $likesCount = $post->likes()->count();
            
            // Broadcast the event
            broadcast(new LikeToggled($post->id_post, $likesCount, 'unliked'))->toOthers();
            
            return response()->json([
                'message' => 'Like supprimé',
                'liked' => false,
                'likes_count' => $likesCount
            ]);
        }

        Like::create([
            'id_user' => $request->user()->id,
            'id_post' => $post->id_post,
        ]);

        \App\Models\Activity::log($request->user()->id, 'like', "A liké un post de " . $post->user->nom);
        // Create notification
        if ($post->id_user !== $request->user()->id) {
            \App\Models\Notification::create([
                'id_user_target' => $post->id_user,
                'id_user_author' => $request->user()->id,
                'id_post' => $post->id_post,
                'type' => 'like'
            ]);
        }

        // Check for new badges for the post author
        \App\Services\BadgeService::checkBadges($post->user);

        $likesCount = $post->likes()->count();
        
        // Broadcast the event
        broadcast(new LikeToggled($post->id_post, $likesCount, 'liked'))->toOthers();

        return response()->json([
            'message' => 'Post liké',
            'liked' => true,
            'likes_count' => $likesCount
        ]);
    }
}
