<?php
// Test script for permissions restore metadata
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "Starting permission restore test...\n";
try {
    $admin = User::first();
    if (! $admin) { echo "No user found to act as actor.\n"; exit(1); }

    $ts = time();
    $perm = Permission::create([
        'name' => "test.permission.{$ts}",
        'slug' => "test.permission.{$ts}",
        'guard_name' => 'web',
        'created_by' => $admin->id,
    ]);

    echo "Created permission id={$perm->id}\n";

    // soft-delete
    $perm->deleted_by = $admin->id;
    $perm->save();
    $perm->delete();
    echo "Soft-deleted permission id={$perm->id}\n";

    Auth::login($admin);

    if (method_exists($perm, 'restoreWithUser')) {
        $perm->restoreWithUser();
    } else {
        $perm->restore();
        $perm->restored_by = $admin->id;
        $perm->restored_at = now();
        $perm->save();
    }

    $perm = $perm->fresh();
    echo "After restore: restored_by={$perm->restored_by}, restored_at={$perm->restored_at}\n";

    $perm->forceDelete();
    echo "Force-deleted test permission id={$perm->id}\n";

    echo "Permission restore test completed.\n";
    exit(0);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
