<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Setting test priorities for roles in branch 1...\n";
echo str_repeat("=", 50) . "\n";

$roles = \App\Models\Role::where('branch_id', 1)->get();

$priority = 10;
foreach ($roles as $role) {
    $oldPriority = $role->priority;
    $role->priority = $priority;
    $role->save();
    echo "Role {$role->id} ({$role->name}): {$oldPriority} -> {$priority}\n";
    $priority += 10;
}

echo str_repeat("=", 50) . "\n";
echo "Updated " . $roles->count() . " roles\n";
