<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        // 🔒 Autorisation via Policy (Admin uniquement)
        $this->authorize('manage', \App\Models\User::class);

        $activities = Activity::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // 🔒 Utilisation de ActivityResource
        return \App\Http\Resources\ActivityResource::collection($activities);
    }
}
