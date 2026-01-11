<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking roles priorities:\n";
echo str_repeat("=", 50) . "\n";

$roles = \App\Models\Role::all(['id', 'name', 'priority', 'branch_id']);

foreach ($roles as $role) {
    $priority = $role->priority ?? 'NULL';
    $branch = $role->branch_id ?? 'NULL';
    echo "Role {$role->id} ({$role->name}): priority = {$priority}, branch = {$branch}\n";
}

echo str_repeat("=", 50) . "\n";
echo "Total roles: " . $roles->count() . "\n";
