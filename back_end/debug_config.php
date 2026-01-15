<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$data = [
    'default_cache' => config('cache.default'),
    'db_database' => config('database.connections.mysql.database'),
    'queue_connection' => config('queue.default'),
];
file_put_contents('debug_output.json', json_encode($data, JSON_PRETTY_PRINT));
