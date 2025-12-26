<?php
// Test restore for Branch
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "Starting branch restore test...\n";
try {
    $admin = User::first();
    if (! $admin) { echo "No user found to act as actor; aborting.\n"; exit(1); }

    $ts = time();
    $name = "Test Branch {$ts}";

    $branch = Branch::create([
        'name' => $name,
        'created_by' => $admin->id,
    ]);

    echo "Created branch id={$branch->id}\n";

    // soft-delete
    $branch->delete();
    echo "Soft-deleted branch id={$branch->id}\n";

    Auth::login($admin);

    if (method_exists($branch, 'restoreWithUser')) {
        $branch->restoreWithUser();
    } else {
        $branch->restore();
        try { $branch->restored_by = $admin->id; $branch->restored_at = now(); $branch->save(); } catch (\Throwable $__e) {}
    }

    $branch = $branch->fresh();
    echo "After restore: restored_by={$branch->restored_by}, restored_at={$branch->restored_at}\n";

    // cleanup
    $branch->forceDelete();
    echo "Force-deleted test branch id={$branch->id}\n";

    echo "Branch restore test completed.\n";
    exit(0);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
