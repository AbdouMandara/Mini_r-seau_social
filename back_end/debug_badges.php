<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Badge;

$user = User::with('badges')->where('nom', 'NOT LIKE', 'SuperAdmin%')->first();
if ($user) {
    echo "User: " . $user->nom . "\n";
    echo "Current Title: " . $user->current_title . "\n";
    echo "Badges count: " . $user->badges->count() . "\n";
    foreach ($user->badges as $badge) {
        echo "- Badge: " . $badge->name . " (ID: " . $badge->id_badge . ")\n";
    }
} else {
    echo "No non-admin user found.\n";
}

echo "\nAll Badges with Criteria:\n";
$badges = Badge::whereNotNull('criteria_type')->get();
foreach ($badges as $badge) {
    echo "- " . $badge->name . " | Type: " . $badge->criteria_type . " | Value: " . $badge->criteria_value . "\n";
}
