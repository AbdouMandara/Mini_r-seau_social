<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Activity;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // For admin to see all reports
        return response()->json(
            Report::with(['reporter', 'post', 'reportedUser'])
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'id_post' => 'nullable|uuid|exists:posts,id_post',
            'id_reported_user' => 'nullable|uuid|exists:users,id',
        ]);

        $report = Report::create([
            'id_user_reporter' => $request->user()->id,
            'id_post' => $request->id_post,
            'id_reported_user' => $request->id_reported_user,
            'reason' => $request->reason,
            'status' => 'pending'
        ]);

        Activity::log($request->user()->id, 'report', "A signalé un " . ($request->id_post ? "post" : "utilisateur"));

        return response()->json([
            'message' => 'Signalement envoyé avec succès',
            'report' => $report
        ], 201);
    }

    public function update(Request $request, Report $report)
    {
        // For admin to resolve/dismiss
        $request->validate([
            'status' => 'required|in:resolved,dismissed'
        ]);

        $report->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Statut du signalement mis à jour',
            'report' => $report
        ]);
    }
}
