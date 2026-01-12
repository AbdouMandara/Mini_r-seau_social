<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Activity;
use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Events\PostCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with(['user.badges', 'comments.user', 'likes'])
            ->where('is_delete', false);

        if ($request->has('tag')) {
            $query->where('tag', $request->tag);
        }

        return PostResource::collection($query->latest()->paginate(10));
    }

    public function store(PostRequest $request)
    {
        $path = null;
        if ($request->hasFile('img_post')) {
            $path = $request->file('img_post')->store('images/post', 'public');
        }

        $post = Post::create([
            'description' => $request->description,
            'img_post' => $path,
            'id_user' => $request->user()->id,
            'allow_comments' => $request->get('allow_comments', true),
            'tag' => $request->tag,
        ]);

        // Detect mentions in the description
        $this->detectMentions($post, $request->description, $request->user());

        Activity::log($request->user()->id, 'post', "A publié un nouveau post : " . substr($post->description, 0, 50) . "...");

        // Check for new badges
        \App\Services\BadgeService::checkBadges($request->user());

        // Broadcast the event
        broadcast(new PostCreated($post))->toOthers();

        return response()->json([
            'message' => 'Post créé avec succès',
            'post' => new PostResource($post->load('user')),
        ], 201);
    }

    public function update(PostRequest $request, Post $post)
    {
        // 🔒 Autorisation via Policy
        $this->authorize('update', $post);

        if ($request->hasFile('img_post')) {
            if ($post->img_post) {
                Storage::disk('public')->delete($post->img_post);
            }
            $post->img_post = $request->file('img_post')->store('images/post', 'public');
        }

        $post->description = $request->description;
        $post->tag = $request->tag;

        if ($request->has('allow_comments')) {
            $post->allow_comments = filter_var($request->allow_comments, FILTER_VALIDATE_BOOLEAN);
        }
        $post->save();

        if ($request->has('description')) {
            $this->detectMentions($post, $request->description, $request->user());
        }

        return response()->json([
            'message' => 'Post modifié avec succès',
            'post' => new PostResource($post->load('user')),
        ]);
    }

    public function destroy(Request $request, Post $post)
    {
        // 🔒 Autorisation via Policy
        $this->authorize('delete', $post);

        $post->is_delete = true;
        $post->save();

        Activity::log($request->user()->id, 'post', "A supprimé un post : " . substr($post->description, 0, 50) . "...");

        return response()->json(['message' => 'Post supprimé avec succès']);
    }

    public function show(Post $post)
    {
        return new PostResource($post->load(['user.badges', 'comments.user', 'likes']));
    }

    public function userPosts(Request $request, $userId = null)
    {
        $id = $userId ?: $request->user()->id;
        return PostResource::collection(
            Post::with(['user.badges', 'comments', 'likes'])
                ->where('id_user', $id)
                ->where('is_delete', false)
                ->latest()
                ->paginate(10)
        );
    }
    public function getTags()
    {
        $tags = Post::whereNotNull('tag')
            ->where('tag', '!=', '')
            ->where('is_delete', false)
            ->select('tag', \DB::raw('count(*) as total'))
            ->groupBy('tag')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json($tags);
    }

    private function detectMentions($post, $content, $author)
    {
        // Detection des mentions @Nom
        preg_match_all('/@([a-zA-Z0-9_\x{00C0}-\x{017F}]+(?:\s[a-zA-Z0-9_\x{00C0}-\x{017F}]+)*)/u', $content, $matches);
        $mentionedNames = array_unique($matches[1]);

        foreach ($mentionedNames as $name) {
            $user = \App\Models\User::where('nom', trim($name))->first();
            if ($user && $user->id !== $author->id) {
                // Ensure we don't spam notifications for the same post mention during update
                $exists = \App\Models\Notification::where('id_user_target', $user->id)
                    ->where('id_user_author', $author->id)
                    ->where('id_post', $post->id_post)
                    ->where('type', 'mention')
                    ->exists();

                if (!$exists) {
                    $notification = \App\Models\Notification::create([
                        'id_user_target' => $user->id,
                        'id_user_author' => $author->id,
                        'id_post' => $post->id_post,
                        'type' => 'mention'
                    ]);

                    // Broadcast notification
                    broadcast(new \App\Events\NotificationSent($notification))->toOthers();
                }
            }
        }
    }
}
