<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Badge;

$badges = Badge::all();

echo "Total Badges found: " . $badges->count() . "\n";
echo "---------------------------------------------------\n";
foreach ($badges as $badge) {
    echo "ID: " . $badge->id_badge . "\n";
    echo "Name: " . $badge->name . "\n";
    echo "Criteria Type: " . $badge->criteria_type . "\n";
    echo "Criteria Value: " . $badge->criteria_value . "\n";
    echo "---------------------------------------------------\n";
}
