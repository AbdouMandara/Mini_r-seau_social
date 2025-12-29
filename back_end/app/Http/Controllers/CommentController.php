<?php

namespace App\Http\Controllers;


use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        if (!$post->allow_comments) {
            return response()->json(['message' => 'Les commentaires sont désactivés pour ce post'], 403);
        }

        $request->validate([
            'contenu' => 'required|string',
        ]);

        $comment = Comment::create([
            'id_user' => $request->user()->id,
            'id_post' => $post->id_post,
            'contenu' => $request->contenu,
        ]);

        // Créer une notification si ce n'est pas son propre post
        if ($post->id_user !== $request->user()->id) {
            \App\Models\Notification::create([
                'id_user_target' => $post->id_user,
                'id_user_author' => $request->user()->id,
                'id_post' => $post->id_post,
                'type' => 'comment'
            ]);
        }

        return response()->json([
            'message' => 'Commentaire ajouté',
            'comment' => $comment->load('user'),
            'comments_count' => $post->comments()->count()
        ], 201);
    }

    public function update(Request $request, Comment $comment)
    {
        if ($comment->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'contenu' => 'required|string',
        ]);

        $comment->update([
            'contenu' => $request->contenu,
        ]);

        return response()->json([
            'message' => 'Commentaire modifié',
            'comment' => $comment->load('user'),
        ]);
    }

    public function destroy(Request $request, Comment $comment)
    {
        if ($comment->id_user !== $request->user()->id && $comment->post->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $postId = $comment->id_post;
        $comment->delete();

        return response()->json([
            'message' => 'Commentaire supprimé',
            'comments_count' => Comment::where('id_post', $postId)->count()
        ]);
    }
}
