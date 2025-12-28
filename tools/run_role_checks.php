<?php
// Run a set of checks against the Laravel app context non-interactively.
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Create kernel and handle a request to set up the container
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "Starting role/auth verification\n";
try {
    $check = Auth::check();
    echo "auth()->check(): " . ($check ? "true" : "false") . "\n";
    if (!$check) {
        echo "User not authenticated — stop here.\n";
        exit(0);
    }

    $user = Auth::user();
    echo "auth()->user()->id: " . ($user->id ?? 'NULL') . "\n";

    // PHASE 2
    $role = null;
    try {
        $role = $user->role ?? null;
    } catch (Throwable $e) {
        echo "Accessing ->role threw: " . $e->getMessage() . "\n";
    }
    echo "user->role: " . ($role ? 'NOT NULL' : 'NULL') . "\n";

    // PHASE 3
    $roleName = null;
    $roleRoleName = null;
    if ($role) {
        $roleName = $role->name ?? null;
        $roleRoleName = $role->role_name ?? null;
    }
    echo "user->role->name: " . ($roleName ?? 'NULL') . "\n";
    echo "user->role->role_name: " . ($roleRoleName ?? 'NULL') . "\n";

    // PHASE 6: getAllPermissions
    $perms = [];
    try {
        $perms = $user->getAllPermissions()->pluck('name')->toArray();
    } catch (Throwable $e) {
        echo "getAllPermissions() threw: " . $e->getMessage() . "\n";
    }
    echo "getAllPermissions count: " . count($perms) . "\n";

    if (count($perms) === 0) {
        echo "Permissions empty. You can run: php artisan db:seed --class=GrantDeveloperAllPermissionsSeeder\n";
    }

} catch (Throwable $e) {
    echo "Exception during checks: " . $e->getMessage() . "\n";
}

echo "Done.\n";
