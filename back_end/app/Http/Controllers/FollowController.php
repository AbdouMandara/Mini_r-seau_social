<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowController extends Controller
{
    public function store(Request $request, User $user)
    {
        $follower = $request->user();

        if ($follower->id === $user->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous suivre vous-même'], 400);
        }

        if (!$follower->isFollowing($user)) {
            $follower->following()->attach($user->id);

            // Check if it's a "follow back" (is the user already following the follower?)
            $isFollowBack = $user->isFollowing($follower);
            $type = $isFollowBack ? 'follow_back' : 'follow';

            // Create Notification
            Notification::create([
                'id_user_target' => $user->id,
                'id_user_author' => $follower->id,
                'type' => $type,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'message' => 'Vous suivez maintenant cet utilisateur',
            'is_following' => true,
            'follower_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        $follower = $request->user();

        if ($follower->isFollowing($user)) {
            $follower->following()->detach($user->id);
        }

        return response()->json([
            'message' => 'Vous ne suivez plus cet utilisateur',
            'is_following' => false,
            'follower_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
        ]);
    }
}
