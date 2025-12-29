<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        return Post::with(['user', 'comments', 'likes'])
            ->where('is_delete', false)
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:100',
            'img_post' => 'nullable|image|mimes:jpeg,jpg,png,svg|max:2048',
            'allow_comments' => 'boolean',
        ]);

        $path = null;
        if ($request->hasFile('img_post')) {
            $path = $request->file('img_post')->store('images/post', 'public');
        }

        $post = Post::create([
            'description' => $request->description,
            'img_post' => $path,
            'id_user' => $request->user()->id,
            'allow_comments' => $request->get('allow_comments', true),
        ]);

        return response()->json([
            'message' => 'Post créé avec succès',
            'post' => $post->load('user'),
        ], 201);
    }

    public function update(Request $request, Post $post)
    {
        if ($post->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'description' => 'required|string|max:100',
            'img_post' => 'nullable|image|mimes:jpeg,jpg,png,svg|max:2048',
            'allow_comments' => 'boolean',
        ]);

        if ($request->hasFile('img_post')) {
            if ($post->img_post) {
                Storage::disk('public')->delete($post->img_post);
            }
            $post->img_post = $request->file('img_post')->store('images/post', 'public');
        }

        $post->description = $request->description;
        if ($request->has('allow_comments')) {
            $post->allow_comments = filter_var($request->allow_comments, FILTER_VALIDATE_BOOLEAN);
        }
        $post->save();

        return response()->json([
            'message' => 'Post modifié avec succès',
            'post' => $post->load('user'),
        ]);
    }

    public function destroy(Request $request, Post $post)
    {
        if ($post->id_user !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $post->is_delete = true;
        $post->save();

        return response()->json(['message' => 'Post supprimé avec succès']);
    }

    public function show(Post $post)
    {
        return $post->load(['user', 'comments.user', 'likes']);
    }

    public function userPosts(Request $request, $userId = null)
    {
        $id = $userId ?: $request->user()->id;
        return Post::with(['user', 'comments', 'likes'])
            ->where('id_user', $id)
            ->where('is_delete', false)
            ->latest()
            ->get();
    }
}
