<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    // Admin: Get all reports
    public function index()
    {
        $reports = Report::with(['reporter', 'post', 'reportedUser'])
                         ->orderBy('created_at', 'desc')
                         ->get();
        return response()->json($reports);
    }

    // User: Submit a report
    public function store(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'id_post' => 'nullable|exists:posts,id_post',
            'id_reported_user' => 'nullable|exists:users,id',
        ]);

        if (!$request->id_post && !$request->id_reported_user) {
            return response()->json(['message' => 'Une cible (post ou utilisateur) est requise.'], 422);
        }

        // Prevent duplicate pending reports from same user for same target
        $existing = Report::where('id_user_reporter', Auth::id())
                          ->where('status', 'pending')
                          ->where(function($q) use ($request) {
                              if ($request->id_post) {
                                  $q->where('id_post', $request->id_post);
                              } else {
                                  $q->where('id_reported_user', $request->id_reported_user);
                              }
                          })->exists();

        if ($existing) {
            return response()->json(['message' => 'Vous avez déjà signalé cet élément. Le signalement est en cours de traitement.'], 409);
        }

        $report = Report::create([
            'id_user_reporter' => Auth::id(),
            'id_post' => $request->id_post,
            'id_reported_user' => $request->id_reported_user,
            'reason' => $request->reason,
            'status' => 'pending'
        ]);

        // Emit Event to Notify Admins
        event(new \App\Events\ReportSubmitted($report));

        return response()->json($report, 201);
    }

    // Admin: Update status
    public function update(Request $request, Report $report)
    {
        $request->validate([
            'status' => 'required|in:pending,resolved,ignored'
        ]);

        $report->status = $request->status;
        $report->save();

        return response()->json($report);
    }
}
