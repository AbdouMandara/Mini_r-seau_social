<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:191|unique:users',
            'password' => 'required|string|min:8',
            'photo_profil' => 'required|image|mimes:jpeg,jpg,png,svg|max:2048',
            'region' => 'required|string',
        ]);

        $path = $request->file('photo_profil')->store('images/profil_user', 'public');

        $user = User::create([
            'nom' => $request->nom,
            'password' => Hash::make($request->password),
            'photo_profil' => $path,
            'region' => $request->region,
        ]);

        // Populate remember_token for security/consistency
        $user->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('nom', $request->nom)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'nom' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Populate remember_token for security/session persistence consistency (requested by user)
        $user->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->save();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
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
            'region' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $user->nom = $request->nom;
        $user->region = $request->region;
        $user->description = $request->description;

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
            'user' => $user,
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
