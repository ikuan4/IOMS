<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Branch;

echo "Checking developer and branch users...\n\n";

$developer = User::where('email', 'ikuan4@gmail.com')->first();

if (!$developer) {
    echo "❌ Developer not found\n";
    exit(1);
}

echo "✓ Developer found: {$developer->name}\n";
echo "  Branch ID: {$developer->branch_id}\n";
echo "  Role: " . ($developer->role ? $developer->role->name : 'No role') . "\n";
echo "  Is Super Admin: " . ($developer->isSuperAdmin() ? 'Yes' : 'No') . "\n\n";

if (!$developer->branch_id) {
    echo "⚠️  Developer has no branch assigned\n";

    // List all branches
    echo "\nAvailable branches:\n";
    $branches = Branch::all();
    foreach($branches as $b) {
        $userCount = User::where('branch_id', $b->id)->count();
        echo "  - {$b->name} (ID: {$b->id}) - {$userCount} users\n";
    }
    exit(0);
}

$branch = Branch::find($developer->branch_id);
if (!$branch) {
    echo "❌ Branch not found for ID: {$developer->branch_id}\n";
    exit(1);
}

echo "✓ Branch: {$branch->name}\n\n";

$users = User::where('branch_id', $branch->id)->get();
echo "Users in branch '{$branch->name}': {$users->count()}\n";

if ($users->count() > 0) {
    echo "\nUser list:\n";
    foreach($users as $u) {
        $roleName = $u->role ? $u->role->name : 'No role';
        $priority = $u->role ? $u->role->priority : 'N/A';
        $active = $u->active ? 'Active' : 'Inactive';
        echo "  - {$u->name} | Role: {$roleName} (Priority: {$priority}) | {$active}\n";
    }
} else {
    echo "  No users found in this branch\n";
}

// List all users across all branches
echo "\n\nAll users in system:\n";
$allUsers = User::with(['role', 'branch'])->get();
echo "Total: {$allUsers->count()}\n\n";

$byBranch = $allUsers->groupBy('branch_id');
foreach($byBranch as $branchId => $branchUsers) {
    $branchName = $branchId ? (Branch::find($branchId)?->name ?? "Unknown Branch (ID: {$branchId})") : 'No Branch';
    echo "{$branchName}:\n";
    foreach($branchUsers as $u) {
        $roleName = $u->role ? $u->role->name : 'No role';
        echo "  - {$u->name} ({$roleName})\n";
    }
    echo "\n";
}
