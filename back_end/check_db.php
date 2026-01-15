<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$exists = Schema::hasTable('cache');
echo "Cache table exists: " . ($exists ? 'Yes' : 'No') . "\n";

if (!$exists) {
    echo "Attempting to run migrations...\n";
    try {
        Artisan::call('migrate', ['--force' => true]);
        echo Artisan::output();
    } catch (\Exception $e) {
        echo "Migration failed: " . $e->getMessage() . "\n";
    }
}
