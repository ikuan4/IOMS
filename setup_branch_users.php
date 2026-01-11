<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;

echo "Assigning all users to Branch 1 and creating roles...\n\n";

$branch = Branch::find(1);
$developer = User::where('email', 'ikuan4@gmail.com')->first();

// Get all users without branches
$usersWithoutBranch = User::whereNull('branch_id')->orWhere('branch_id', 0)->get();

echo "Found {$usersWithoutBranch->count()} users without branch\n\n";

// Create or get roles
$managerRole = Role::where('slug', 'manager')->where('branch_id', $branch->id)->first();
if (!$managerRole) {
    $managerRole = Role::create([
        'name' => 'Manager',
        'slug' => 'manager',
        'description' => 'Branch Manager with elevated permissions',
        'is_active' => true,
        'priority' => 10,
        'branch_id' => $branch->id,
        'guard_name' => 'web',
        'created_by' => $developer->id ?? 1,
    ]);
    echo "✓ Created Manager role (Priority: 10)\n";
}

$staffRole = Role::where('slug', 'staff')->where('branch_id', $branch->id)->first();
if (!$staffRole) {
    $staffRole = Role::create([
        'name' => 'Staff',
        'slug' => 'staff',
        'description' => 'Regular staff member',
        'is_active' => true,
        'priority' => 20,
        'branch_id' => $branch->id,
        'guard_name' => 'web',
        'created_by' => $developer->id ?? 1,
    ]);
    echo "✓ Created Staff role (Priority: 20)\n";
}

// Assign users to branch and roles
foreach ($usersWithoutBranch as $user) {
    $user->branch_id = $branch->id;

    if (!$user->role_id) {
        // Assign staff role to users without roles
        $user->role_id = $staffRole->id;
        echo "✓ Assigned {$user->name} to {$branch->name} with Staff role\n";
    } else {
        echo "✓ Assigned {$user->name} to {$branch->name} (keeping existing role)\n";
    }

    if (!$user->active) {
        $user->active = true;
    }

    $user->save();
}

// Create additional test users if needed
$userCount = User::where('branch_id', $branch->id)->count();
if ($userCount < 4) {
    echo "\nCreating additional test users...\n";

    // Create unique users
    for ($i = $userCount; $i < 4; $i++) {
        $randomNumber = rand(3000000000, 3999999999);
        $role = $i === 1 ? $managerRole : $staffRole;

        User::create([
            'name' => "Test User $i",
            'email' => "testuser{$i}@example.com",
            'mobile' => (string)$randomNumber,
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'branch_id' => $branch->id,
            'active' => true,
            'created_by' => $developer->id ?? 1,
        ]);

        echo "✓ Created Test User $i ({$role->name})\n";
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "FINAL STATE\n";
echo str_repeat('=', 50) . "\n\n";

$allUsers = User::where('branch_id', $branch->id)->with('role')->orderBy('name')->get();
echo "Users in '{$branch->name}': {$allUsers->count()}\n\n";

foreach($allUsers as $u) {
    $roleName = $u->role ? $u->role->name : 'No role';
    $priority = $u->role ? $u->role->priority : 'N/A';
    $active = $u->active ? '✓ Active' : '✗ Inactive';
    echo "  {$u->name}\n";
    echo "    Email: {$u->email}\n";
    echo "    Role: {$roleName} (Priority: {$priority})\n";
    echo "    Status: {$active}\n\n";
}

echo "\nRoles in '{$branch->name}':\n";
$roles = Role::where('branch_id', $branch->id)->orderBy('priority')->get();
foreach($roles as $r) {
    $userCount = User::where('role_id', $r->id)->count();
    echo "  {$r->name} (Priority: {$r->priority}) - {$userCount} users\n";
}
