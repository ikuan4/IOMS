<?php
// Test restore for Role
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "Starting role restore test...\n";
try {
    $admin = User::first();
    if (! $admin) { echo "No user found to act as actor; aborting.\n"; exit(1); }

    $ts = time();
    $name = "Test Role {$ts}";
    $slug = 'test-role-' . $ts;

    $role = Role::create([
        'name' => $name,
        'slug' => $slug,
        'description' => 'Temporary role for restore test',
        'is_active' => true,
    ]);

    echo "Created role id={$role->id}\n";

    // soft-delete
    $role->delete();
    echo "Soft-deleted role id={$role->id}\n";

    Auth::login($admin);

    if (method_exists($role, 'restoreWithUser')) {
        $role->restoreWithUser();
    } else {
        $role->restore();
        try { $role->restored_by = $admin->id; $role->restored_at = now(); $role->save(); } catch (\Throwable $__e) {}
    }

    $role = $role->fresh();
    echo "After restore: restored_by={$role->restored_by}, restored_at={$role->restored_at}\n";

    // cleanup
    $role->forceDelete();
    echo "Force-deleted test role id={$role->id}\n";

    echo "Role restore test completed.\n";
    exit(0);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
