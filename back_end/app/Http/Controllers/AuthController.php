<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Activity;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $path = $request->file('photo_profil')->store('images/profil_user', 'public');

        $user = User::create([
            'nom' => $request->nom,
            // ⚠️ En Laravel 12, le cast 'hashed' dans le modèle User hache déjà le mot de passe.
            // Le hacher ici avec Hash::make() créerait un double-hachage invalide.
            'password' => $request->password,
            'photo_profil' => $path,
            'etablissement' => $request->etablissement,
            'filiere' => $request->filiere,
            'niveau' => $request->niveau,
        ]);

        Auth::login($user);

        Activity::log($user->id, 'inscription', "S'est inscrit sur la plateforme");
        
        event(new \App\Events\UserRegistered($user));

        return response()->json([
            'message' => 'Inscription réussie',
            'user' => new UserResource($user),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nom' => 'required|string',
            'password' => 'required|string',
        ]);

        \Log::info('Login attempt', ['nom' => $credentials['nom']]);
        
        $user = User::where('nom', $credentials['nom'])->first();
        
        if (!$user) {
            \Log::warning('Login failed: User not found', ['nom' => $credentials['nom']]);
        } else {
            $passwordOk = \Hash::check($credentials['password'], $user->password);
            \Log::info('Login debug', [
                'user_found' => true,
                'password_check' => $passwordOk,
                'is_admin' => $user->is_admin,
                'is_blocked' => $user->is_blocked
            ]);
        }

        if (Auth::attempt($credentials)) {
            // Retrieve authenticated user. If null (unexpected), fallback to the manually queried user.
            $authUser = Auth::user() ?? $user;
            
            \Log::info('Login success', ['nom' => $credentials['nom'], 'id' => $authUser->id]);
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Connexion réussie',
                'user' => new UserResource($authUser),
                'debug_hit' => true,
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
        return new UserResource($request->user());
    }

    public function getUserByNom(Request $request, $nom)
    {
        // Try to find by slug first, then name (decoding URL encoded spaces if needed)
        $user = User::where('slug', $nom)->orWhere('nom', str_replace('_', ' ', $nom))->firstOrFail();
        
        $user->loadCount(['likes', 'comments', 'followers', 'following']);
        
        // Add is_following attribute if user is authenticated
        if ($auth = $request->user('sanctum')) {
            $user->is_following = $auth->isFollowing($user);
        } else {
            $user->is_following = false;
        }

        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'nom' => 'required|string|max:191|unique:users,nom,' . $user->id,
            'photo_profil' => 'nullable|image|mimes:jpeg,jpg,png,svg|max:2048',
            'etablissement' => 'required|string|max:191',
            'filiere' => 'required|string|in:GL,GLT,SWE,MVC,LTM',
            'niveau' => 'required|string|in:1,2',
            'bio' => 'nullable|string',
        ]);

        $user->nom = $request->nom;
        $user->etablissement = $request->etablissement;
        $user->filiere = $request->filiere;
        $user->niveau = $request->niveau;
        $user->bio = $request->bio;

        if ($request->hasFile('photo_profil')) {
            // Delete old photo if exists
            if ($user->photo_profil) {
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
                    ->where('is_admin', false)
                    ->select('id', 'nom', 'photo_profil', 'slug')
                    ->limit(10)
                    ->get();
        
        // Append full URL for photo_profil if needed
        foreach ($users as $user) {
             if ($user->photo_profil && !str_starts_with($user->photo_profil, 'http')) {
                // We don't need to append full url here if frontend handles it, 
                // but let's keep it consistent or just return path. 
                // Front end App.vue handles /storage/ prefix.
             }
        }

        return response()->json($users);
    }
}
