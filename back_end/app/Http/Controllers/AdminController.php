<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function getDashboardStats()
    {
        // 🔒 Vérification Admin via Policy
        $this->authorize('accessAdminPanel', User::class);

        $adminId = request()->user()->id;

        return response()->json([
            'total_users' => User::where('is_admin', false)->count(),
            'total_posts' => Post::where('is_delete', false)->count(),
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
        ]);
    }

    public function getUsers(Request $request)
    {
        $this->authorize('accessAdminPanel', User::class);

        $users = User::where('is_admin', false)
            ->withCount(['posts', 'followers', 'following', 'likes', 'comments'])
            ->latest()
            ->paginate(10);

        // 🔒 Utilisation systématique de UserResource
        return UserResource::collection($users);
    }

    public function getFeedbacks()
    {
        $this->authorize('accessAdminPanel', User::class);

        $feedbacks = Feedback::with('user')->latest()->get();
        
        // 🔒 Utilisation systématique de FeedbackResource
        return \App\Http\Resources\FeedbackResource::collection($feedbacks);
    }

    public function toggleBlock(Request $request, User $user)
    {
        $this->authorize('accessAdminPanel', User::class);

        $request->validate([
            'admin_password' => 'required|string',
        ]);

        $admin = $request->user();

        if (!Hash::check($request->admin_password, $admin->password)) {
            return response()->json(['message' => 'Mot de passe administrateur incorrect'], 403);
        }

        if ($user->is_admin) {
            return response()->json(['message' => 'Impossible de bloquer un administrateur'], 403);
        }

        $user->is_blocked = !$user->is_blocked;
        $user->save();

        \App\Models\Activity::log(Auth::id(), 'admin', ($user->is_blocked ? "A bloqué" : "A débloqué") . " l'utilisateur " . $user->nom);

        return response()->json([
            'message' => "Utilisateur " . ($user->is_blocked ? 'bloqué' : 'débloqué') . " avec succès",
            'user' => new UserResource($user)
        ]);
    }
}
