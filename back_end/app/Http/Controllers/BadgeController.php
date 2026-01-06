<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BadgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(\App\Models\Badge::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'required|string|max:50',
            'color' => 'required|string|max:20',
            'criteria_type' => 'nullable|string|in:posts_count,likes_received_count',
            'criteria_value' => 'required_with:criteria_type|integer|min:1',
        ]);

        $badge = \App\Models\Badge::create($validated);

        return response()->json($badge, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Badge $badge)
    {
        return response()->json($badge);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\Illuminate\Http\Request $request, \App\Models\Badge $badge)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'sometimes|string|max:50',
            'color' => 'sometimes|string|max:20',
            'criteria_type' => 'nullable|string|in:posts_count,likes_received_count',
            'criteria_value' => 'required_with:criteria_type|integer|min:1',
        ]);

        $badge->update($validated);

        return response()->json($badge);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Badge $badge)
    {
        $badge->delete();
        return response()->json(null, 204);
    }
}
