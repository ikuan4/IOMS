<?php
// Bootstraps the Laravel app and runs a seeder class directly.
// Run the canonical permissions seeder by default
$class = '\\Database\\Seeders\\CanonicalPermissionsSeeder';

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (!class_exists($class)) {
    echo "Seeder class $class not found.\n";
    exit(1);
}

$seeder = new $class();
if (!method_exists($seeder, 'run')) {
    echo "Seeder $class has no run() method.\n";
    exit(1);
}

echo "Running seeder $class...\n";
$seeder->run();
echo "Done.\n";
