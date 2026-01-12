<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        // 🔒 Autorisation via Policy
        $this->authorize('create', Feedback::class);

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:255',
        ]);

        $feedback = Feedback::create([
            'user_id' => $request->user()->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Votre feedback a été reçu, merci !',
            'feedback' => new \App\Http\Resources\FeedbackResource($feedback)
        ]);
    }
}
