<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Badge;

$badges = Badge::all();

foreach ($badges as $badge) {
    echo "Badge: " . $badge->name . " | Icon: '" . $badge->icon . "'\n";
}
