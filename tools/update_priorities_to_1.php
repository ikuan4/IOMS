<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Updating all roles with priority 100 to priority 1...\n";
echo str_repeat("=", 50) . "\n";

$count = \App\Models\Role::where('priority', 100)->update(['priority' => 1]);

echo "Updated {$count} roles\n";
echo str_repeat("=", 50) . "\n";

echo "\nCurrent role priorities:\n";
$roles = \App\Models\Role::all(['id', 'name', 'priority']);
foreach ($roles as $role) {
    echo "Role {$role->id} ({$role->name}): priority = {$role->priority}\n";
}
