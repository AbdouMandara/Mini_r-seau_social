<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\BadgeService;

echo "Starting Badge Sync...\n";

$users = User::all();
$count = 0;

foreach ($users as $user) {
    echo "Checking user: " . $user->nom . "\n";
    try {
        BadgeService::checkBadges($user);
        $count++;
    } catch (\Exception $e) {
        echo "Error checking user " . $user->nom . ": " . $e->getMessage() . "\n";
    }
}

echo "---------------------------------------------------\n";
echo "Checked " . $count . " users.\n";
echo "Done.\n";
