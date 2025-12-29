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

    public function getUserByNom($nom)
    {
        $user = User::where('nom', $nom)->firstOrFail();
        $user->loadCount(['likes', 'comments']);
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
}
