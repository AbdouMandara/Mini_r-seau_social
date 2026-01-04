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

        \App\Models\Activity::log($request->user()->id, 'commentaire', "A commenté un post de " . $post->user->nom);

        // Detection des mentions @Nom
        preg_match_all('/@([a-zA-Z0-9_\x{00C0}-\x{017F}]+(?:\s[a-zA-Z0-9_\x{00C0}-\x{017F}]+)*)/u', $request->contenu, $matches);
        $mentionedNames = array_unique($matches[1]);

        foreach ($mentionedNames as $name) {
            $user = \App\Models\User::where('nom', trim($name))->first();
            if ($user && $user->id !== $request->user()->id) {
                \App\Models\Notification::create([
                    'id_user_target' => $user->id,
                    'id_user_author' => $request->user()->id,
                    'id_post' => $post->id_post,
                    'type' => 'mention'
                ]);
            }
        }

        // Créer une notification si ce n'est pas son propre post (de type 'comment')
        if ($post->id_user !== $request->user()->id) {
            // On évite de notifier deux fois si le proprio est déjà mentionné? 
            // D'habitude on notifie 'mention' en priorité ou les deux. 
            // Ici on va faire simple : si pas déjà notifié pour mention sur ce post à cet instant.
            $alreadyNotified = \App\Models\Notification::where('id_user_target', $post->id_user)
                ->where('id_user_author', $request->user()->id)
                ->where('id_post', $post->id_post)
                ->where('type', 'mention')
                ->exists();

            if (!$alreadyNotified) {
                \App\Models\Notification::create([
                    'id_user_target' => $post->id_user,
                    'id_user_author' => $request->user()->id,
                    'id_post' => $post->id_post,
                    'type' => 'comment'
                ]);
            }
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
