<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Http\Requests\CommentRequest;
use App\Http\Resources\CommentResource;
use App\Events\CommentAdded;
use App\Events\NotificationSent;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(CommentRequest $request, Post $post)
    {
        if (!$post->allow_comments) {
            return response()->json(['message' => 'Les commentaires sont désactivés pour ce post'], 403);
        }

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
            $alreadyNotified = \App\Models\Notification::where('id_user_target', $post->id_user)
                ->where('id_user_author', $request->user()->id)
                ->where('id_post', $post->id_post)
                ->where('type', 'mention')
                ->exists();

            if (!$alreadyNotified) {
                $notification = \App\Models\Notification::create([
                    'id_user_target' => $post->id_user,
                    'id_user_author' => $request->user()->id,
                    'id_post' => $post->id_post,
                    'type' => 'comment'
                ]);
                
                // Broadcast notification
                broadcast(new NotificationSent($notification))->toOthers();
            }
        }

        // Broadcast comment added
        broadcast(new CommentAdded($comment))->toOthers();

        // Check badges for the user who commented
        \App\Services\BadgeService::checkBadges($request->user());

        return response()->json([
            'message' => 'Commentaire ajouté',
            'comment' => new CommentResource($comment->load('user')),
            'comments_count' => $post->comments()->count()
        ], 201);
    }

    public function update(CommentRequest $request, Comment $comment)
    {
        if ($comment->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $comment->update([
            'contenu' => $request->contenu,
        ]);

        return response()->json([
            'message' => 'Commentaire modifié',
            'comment' => new CommentResource($comment->load('user')),
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
