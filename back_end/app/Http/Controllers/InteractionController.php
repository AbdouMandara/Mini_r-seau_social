<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Comment;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $likes = Like::with('post')
            ->where('id_user', $user->id)
            ->get()
            ->map(function ($like) {
                return [
                    'id' => 'like_' . $like->id,
                    'type' => 'like',
                    'created_at' => $like->created_at,
                    'post' => $like->post ? [
                        'id_post' => $like->post->id_post,
                        'description' => $like->post->description,
                        'img_post' => $like->post->img_post,
                    ] : null,
                ];
            });

        $comments = Comment::with('post')
            ->where('id_user', $user->id)
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => 'comment_' . $comment->id,
                    'type' => 'comment',
                    'contenu' => $comment->contenu,
                    'created_at' => $comment->created_at,
                    'post' => $comment->post ? [
                        'id_post' => $comment->post->id_post,
                        'description' => $comment->post->description,
                        'img_post' => $comment->post->img_post,
                    ] : null,
                ];
            });

        $interactions = $likes->concat($comments)
            ->sortByDesc('created_at')
            ->values();

        return response()->json($interactions);
    }
}
