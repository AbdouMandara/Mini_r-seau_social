<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function getDashboardStats()
    {
        $totalUsers = User::count();
        $totalPosts = Post::count();
        $totalLikes = Like::count();
        $totalComments = Comment::count();

        return response()->json([
            'total_users' => $totalUsers,
            'total_posts' => $totalPosts,
            'total_likes' => $totalLikes,
            'total_comments' => $totalComments,
        ]);
    }

    public function getUsers(Request $request)
    {
        $users = User::where('is_admin', false)
            ->withCount(['posts', 'followers', 'following'])
            ->latest()
            ->paginate(10);

        return response()->json($users);
    }

    public function getFeedbacks()
    {
        $feedbacks = Feedback::with('user:id,nom,photo_profil')->latest()->get();
        return response()->json($feedbacks);
    }

    public function toggleBlock(Request $request, User $user)
    {
        $request->validate([
            'admin_password' => 'required|string',
        ]);

        $admin = $request->user();

        if (!Hash::check($request->admin_password, $admin->password)) {
            return response()->json(['message' => 'Mot de passe administrateur incorrect'], 403);
        }

        $user->is_blocked = !$user->is_blocked;
        $user->save();

        $status = $user->is_blocked ? 'bloqué' : 'débloqué';

        return response()->json([
            'message' => "Utilisateur $status avec succès",
            'is_blocked' => $user->is_blocked
        ]);
    }
}
