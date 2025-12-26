<?php
// Small script to test restore metadata (run from project root)
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "Starting restore test...\n";
try {
    $admin = User::first();
    if (! $admin) {
        echo "No existing users found to act as actor; aborting.\n";
        exit(1);
    }

    $ts = time();
    $mobile = '999' . substr($ts, -7);
    $email = "test_restore_{$ts}@example.test";

    $user = User::create([
        'name' => "Test Restore User {$ts}",
        'mobile' => $mobile,
        'email' => $email,
        'password' => Hash::make('password'),
        'active' => true,
        'role_id' => $admin->role_id ?? null,
        'created_by' => $admin->id,
    ]);

    echo "Created user id={$user->id}\n";

    // soft-delete
    $user->deleted_by = $admin->id;
    $user->save();
    $user->delete();
    echo "Soft-deleted user id={$user->id}\n";

    // login as admin and restore
    Auth::login($admin);

    if (method_exists($user, 'restoreWithUser')) {
        $user->restoreWithUser();
    } else {
        $user->restore();
        $user->restored_by = $admin->id;
        $user->restored_at = now();
        $user->save();
    }

    $user = $user->fresh();
    echo "After restore: restored_by={$user->restored_by}, restored_at={$user->restored_at}\n";

    // cleanup
    $user->forceDelete();
    echo "Force-deleted test user id={$user->id}\n";

    echo "Restore test completed successfully.\n";
    exit(0);
} catch (\Throwable $e) {
    echo "Error during restore test: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
