<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Badge;

// Update 'Le Posteur'
Badge::where('name', 'Le Posteur')->update(['icon' => 'edit_note']);

// Update 'Populaire'
Badge::where('name', 'Populaire')->update(['icon' => 'whatshot']);

// Update 'Expert'
Badge::where('name', 'Expert')->update(['icon' => 'school']);

// Update 'Validateur' (if exists) from check-circle to check_circle
Badge::where('name', 'Validateur')->update(['icon' => 'check_circle']);

echo "Icons updated to Material Symbols.\n";
