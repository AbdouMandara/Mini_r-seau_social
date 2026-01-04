<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Activity;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with(['user', 'comments.user', 'likes'])
            ->where('is_delete', false);

        if ($request->has('tag')) {
            $query->where('tag', $request->tag);
        }

        if ($request->has('filiere')) {
            $query->where('filiere', $request->filiere);
        }

        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:100',
            'img_post' => 'nullable|image|mimes:jpeg,jpg,png,svg|max:2048',
            'allow_comments' => 'boolean',
            'tag' => 'required|string|in:etude,divertissement,info,programmation,maths,devoir',
            'filiere' => 'required|string|in:GL,GLT,SWE,MVC,LTM',
            'niveau' => 'required|string|in:1,2',
            'matiere' => 'nullable|string|max:191',
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
            'tag' => $request->tag,
            'filiere' => $request->filiere,
            'niveau' => $request->niveau,
            'matiere' => $request->matiere,
        ]);

        Activity::log($request->user()->id, 'post', "A publié un nouveau post : " . substr($post->description, 0, 50) . "...");

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
            'tag' => 'required|string|in:etude,divertissement,info,programmation,maths,devoir',
            'filiere' => 'required|string|in:GL,GLT,SWE,MVC,LTM',
            'niveau' => 'required|string|in:1,2',
            'matiere' => 'nullable|string|max:191',
        ]);

        if ($request->hasFile('img_post')) {
            if ($post->img_post) {
                Storage::disk('public')->delete($post->img_post);
            }
            $post->img_post = $request->file('img_post')->store('images/post', 'public');
        }

        $post->description = $request->description;
        $post->tag = $request->tag;
        $post->filiere = $request->filiere;
        $post->niveau = $request->niveau;
        $post->matiere = $request->matiere;

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

        Activity::log($request->user()->id, 'post', "A supprimé un post : " . substr($post->description, 0, 50) . "...");

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
