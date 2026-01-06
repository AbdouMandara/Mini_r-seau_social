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
        $adminId = request()->user()->id; // Changed $request to request() helper

        $stats = [
            'total_users' => User::where('is_admin', false)->count(),
            'total_posts' => Post::count(),
            'total_likes' => Like::count(),
            'total_comments' => Comment::count(),
            'unread_reports' => Notification::where('id_user_target', $adminId)
                                    ->where('type', 'report')
                                    ->where('is_read', false)
                                    ->count(),
            'unread_new_users' => Notification::where('id_user_target', $adminId)
                                    ->where('type', 'new_user')
                                    ->where('is_read', false)
                                    ->count(),
        ];

        return response()->json($stats);
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
