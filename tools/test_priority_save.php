<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing manual priority save...\n";
echo str_repeat("=", 50) . "\n";

// Get a role from branch 1
$role = \App\Models\Role::where('branch_id', 1)->first();

if (!$role) {
    echo "No role found in branch 1\n";
    exit(1);
}

echo "Role: {$role->id} - {$role->name}\n";
echo "Current priority: " . ($role->priority ?? 'NULL') . "\n";

// Try to update it
$newPriority = 25;
$role->priority = $newPriority;
$saved = $role->save();

echo "Attempted to set priority to: {$newPriority}\n";
echo "Save result: " . ($saved ? 'SUCCESS' : 'FAILED') . "\n";

// Reload from database
$role->refresh();
echo "Priority after refresh: " . ($role->priority ?? 'NULL') . "\n";

echo str_repeat("=", 50) . "\n";
