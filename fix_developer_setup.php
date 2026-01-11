<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;

echo "Fixing developer user setup...\n\n";

$developer = User::where('email', 'ikuan4@gmail.com')->first();

if (!$developer) {
    echo "❌ Developer not found\n";
    exit(1);
}

// Find or create Branch 1
$branch = Branch::find(1);
if (!$branch) {
    echo "Creating Branch 1...\n";
    $branch = Branch::create([
        'name' => 'Main Branch',
        'created_by' => 1,
    ]);
}

// Find or create Developer role
$developerRole = Role::where('slug', 'developer')->orWhere('name', 'Developer')->first();
if (!$developerRole) {
    echo "Creating Developer role...\n";
    $developerRole = Role::create([
        'name' => 'Developer',
        'slug' => 'developer',
        'description' => 'Super Administrator with full system access',
        'is_active' => true,
        'priority' => 1,
        'branch_id' => $branch->id,
        'guard_name' => 'web',
        'created_by' => 1,
    ]);
}

// Update developer user
echo "Updating developer user...\n";
$developer->branch_id = $branch->id;
$developer->role_id = $developerRole->id;
$developer->active = true;
$developer->save();

echo "✓ Developer updated:\n";
echo "  - Branch: {$branch->name} (ID: {$branch->id})\n";
echo "  - Role: {$developerRole->name} (Priority: {$developerRole->priority})\n";
echo "  - Active: Yes\n\n";

// Create some test users if branch has no users
$usersInBranch = User::where('branch_id', $branch->id)->where('id', '!=', $developer->id)->count();

if ($usersInBranch === 0) {
    echo "Branch has only 1 user (developer). Creating test users...\n\n";

    // Create Manager role
    $managerRole = Role::firstOrCreate(
        ['slug' => 'manager', 'branch_id' => $branch->id],
        [
            'name' => 'Manager',
            'description' => 'Branch Manager',
            'is_active' => true,
            'priority' => 10,
            'guard_name' => 'web',
            'created_by' => $developer->id,
        ]
    );

    // Create Staff role
    $staffRole = Role::firstOrCreate(
        ['slug' => 'staff', 'branch_id' => $branch->id],
        [
            'name' => 'Staff',
            'description' => 'Branch Staff',
            'is_active' => true,
            'priority' => 20,
            'guard_name' => 'web',
            'created_by' => $developer->id,
        ]
    );

    // Create test manager
    $manager = User::create([
        'name' => 'Test Manager',
        'email' => 'manager@test.com',
        'mobile' => '1111111111',
        'password' => bcrypt('password'),
        'role_id' => $managerRole->id,
        'branch_id' => $branch->id,
        'active' => true,
        'created_by' => $developer->id,
    ]);

    // Create test staff
    $staff = User::create([
        'name' => 'Test Staff',
        'email' => 'staff@test.com',
        'mobile' => '2222222222',
        'password' => bcrypt('password'),
        'role_id' => $staffRole->id,
        'branch_id' => $branch->id,
        'active' => true,
        'created_by' => $developer->id,
    ]);

    echo "✓ Created test users:\n";
    echo "  - Test Manager (manager@test.com) - Priority: 10\n";
    echo "  - Test Staff (staff@test.com) - Priority: 20\n\n";
}

echo "\n✓ Setup complete!\n\n";
echo "Summary:\n";
$allUsers = User::where('branch_id', $branch->id)->with('role')->get();
echo "Users in '{$branch->name}': {$allUsers->count()}\n";
foreach($allUsers as $u) {
    $roleName = $u->role ? $u->role->name : 'No role';
    $priority = $u->role ? $u->role->priority : 'N/A';
    echo "  - {$u->name} | {$roleName} (Priority: {$priority})\n";
}
