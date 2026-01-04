<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        // Only admins can see activities
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $activities = Activity::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($activities);
    }
}
