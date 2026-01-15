<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\BadgeResource;

class BadgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Les badges sont publics
        return BadgeResource::collection(\App\Models\Badge::all());
    }

    public function store(Request $request)
    {
        // 🔒 Autorisation via Policy (Admin uniquement)
        $this->authorize('manage', \App\Models\Badge::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'required|string|max:50',
            'color' => 'required|string|max:20',
            'criteria_type' => 'nullable|string|in:posts_count,likes_received_count,followers_count,following_count,comments_count,post_reports_count,is_certified',
            'criteria_value' => 'required_with:criteria_type|integer|min:0',
        ]);

        $badge = \App\Models\Badge::create($validated);

        return response()->json([
            'message' => 'Badge créé avec succès',
            'badge' => new BadgeResource($badge)
        ], 201);
    }

    public function show(\App\Models\Badge $badge)
    {
        return new BadgeResource($badge);
    }

    public function update(Request $request, \App\Models\Badge $badge)
    {
        // 🔒 Autorisation via Policy (Admin uniquement)
        $this->authorize('manage', \App\Models\Badge::class);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'sometimes|string|max:50',
            'color' => 'sometimes|string|max:20',
            'criteria_type' => 'nullable|string|in:posts_count,likes_received_count,followers_count,following_count,comments_count,post_reports_count,is_certified',
            'criteria_value' => 'required_with:criteria_type|integer|min:0',
        ]);

        $badge->update($validated);

        return response()->json([
            'message' => 'Badge mis à jour avec succès',
            'badge' => new BadgeResource($badge)
        ]);
    }

    public function destroy(\App\Models\Badge $badge)
    {
        // 🔒 Autorisation via Policy (Admin uniquement)
        $this->authorize('manage', \App\Models\Badge::class);

        $badge->delete();
        return response()->json(['message' => 'Badge supprimé'], 204);
    }
}
