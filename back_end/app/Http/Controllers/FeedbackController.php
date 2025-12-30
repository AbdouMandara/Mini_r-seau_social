<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\FeedbackMail;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:255',
        ]);

        $user = $request->user();

        $data = [
            'name' => $user->nom,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ];

        // Send Email using allow synchronous send for immediate feedback
        try {
            Mail::to('abdoumandara@gmail.com')->send(new FeedbackMail($data));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'envoi du mail: ' . $e->getMessage()], 500); 
        }

        return response()->json(['message' => 'Votre feedback a été reçu, merci !']);
    }
}
