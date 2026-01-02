<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('id_user_target', $request->user()->id)
            ->with(['author', 'post'])
            ->latest()
            ->get();

        return response()->json($notifications);
    }

    public function markAsRead(Request $request)
    {
        $count = Notification::where('id_user_target', $request->user()->id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json(['message' => 'Notifications marquées comme lues', 'updated' => $count]);
    }
}
