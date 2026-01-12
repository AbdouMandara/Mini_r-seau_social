<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Activity;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $path = null;
        if ($request->hasFile('photo_profil')) {
            $path = $request->file('photo_profil')->store('images/profil_user', 'public');
        } else {
            // Generate default avatar if no photo is provided
            $path = 'https://ui-avatars.com/api/?name=' . urlencode($request->nom) . '&background=random&color=fff';
        }

        $user = User::create([
            'nom' => $request->nom,
            // ⚠️ En Laravel 12, le cast 'hashed' dans le modèle User hache déjà le mot de passe.
            // Le hacher ici avec Hash::make() créerait un double-hachage invalide.
            'password' => $request->password,
            'photo_profil' => $path,
        ]);

        Auth::login($user);

        Activity::log($user->id, 'inscription', "S'est inscrit sur la plateforme");

        event(new \App\Events\UserRegistered($user));

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => new UserResource($user),
        ]);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        Log::info('Login attempt', ['nom' => $credentials['nom']]);

        $user = User::where('nom', $credentials['nom'])->first();

        if (Auth::attempt($credentials)) {
            $authUser = Auth::user() ?? $user;

            Log::info('Login success', ['nom' => $credentials['nom'], 'id' => $authUser->id]);
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Connexion réussie',
                'user' => new UserResource($authUser),
            ]);
        }

        throw ValidationException::withMessages([
            'nom' => ['Les identifiants sont incorrects.'],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    public function profile(Request $request)
    {
        // 🔒 Utilisation systématique de UserResource
        return new UserResource($request->user()->load('badges'));
    }

    public function getUserByNom(Request $request, $nom)
    {
        $user = User::where('slug', $nom)->orWhere('nom', str_replace('_', ' ', $nom))->firstOrFail();

        $user->loadCount(['likes', 'comments', 'followers', 'following']);
        $user->load(['badges', 'posts' => function($query) {
             $query->where('is_delete', false)->latest()->take(10);
        }]);

        if ($auth = $request->user('sanctum')) {
            $user->is_following = $auth->isFollowing($user);
        } else {
            $user->is_following = false;
        }

        // 🔒 Utilisation systématique de UserResource pour éviter l'exposition de is_admin
        return new UserResource($user);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();

        // 🔒 Vérification via Policy (optionnel ici car on utilise $request->user(), mais bonne pratique)
        // $this->authorize('update', $user);

        $user->nom = $request->nom;
        $user->bio = $request->bio;

        if ($request->hasFile('photo_profil')) {
            if ($user->photo_profil && !str_starts_with($user->photo_profil, 'http')) {
                Storage::disk('public')->delete($user->photo_profil);
            }
            $path = $request->file('photo_profil')->store('images/profil_user', 'public');
            $user->photo_profil = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => new UserResource($user),
        ]);
    }

    public function searchUsers(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json([]);
        }

        $users = User::where('nom', 'LIKE', "%{$query}%")
                    ->where('is_blocked', false) // 🔒 Sécurité : Ne pas afficher les utilisateurs bloqués
                    ->limit(10)
                    ->get();

        // 🔒 Utilisation systématique de UserResource
        return UserResource::collection($users);
    }
}
