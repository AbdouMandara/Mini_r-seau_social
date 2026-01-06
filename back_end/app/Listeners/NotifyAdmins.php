<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\Notification;
use App\Events\ReportSubmitted;
use App\Events\UserRegistered;

class NotifyAdmins
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // Get all admins
        $admins = User::where('is_admin', true)->get();

        foreach ($admins as $admin) {
            if ($event instanceof ReportSubmitted) {
                $report = $event->report;
                Notification::create([
                    'id_user_target' => $admin->id,
                    'id_user_author' => $report->id_user_reporter,
                    'id_post' => $report->id_post, // Can be null if reporting a user
                    'type' => 'report',
                    'is_read' => false
                ]);
            } elseif ($event instanceof UserRegistered) {
                $newUser = $event->user;
                Notification::create([
                    'id_user_target' => $admin->id,
                    'id_user_author' => $newUser->id,
                    'type' => 'new_user',
                    'is_read' => false
                ]);
            }
        }
    }
}
