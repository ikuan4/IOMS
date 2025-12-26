<?php
// Test script for audit_logs restore metadata
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "Starting audit_log restore test...\n";
try {
    $admin = User::first();
    if (! $admin) { echo "No user found to act as actor.\n"; exit(1); }

    $ts = time();
    $log = AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'test.action',
        'auditable_type' => null,
        'auditable_id' => null,
        'old_values' => [],
        'new_values' => [],
    ]);

    echo "Created audit_log id={$log->id}\n";

    // soft-delete
    $log->deleted_by = $admin->id;
    $log->save();
    $log->delete();
    echo "Soft-deleted audit_log id={$log->id}\n";

    Auth::login($admin);

    if (method_exists($log, 'restoreWithUser')) {
        $log->restoreWithUser();
    } else {
        $log->restore();
        $log->restored_by = $admin->id;
        $log->restored_at = now();
        $log->save();
    }

    $log = $log->fresh();
    echo "After restore: restored_by={$log->restored_by}, restored_at={$log->restored_at}\n";

    $log->forceDelete();
    echo "Force-deleted test audit_log id={$log->id}\n";

    echo "AuditLog restore test completed.\n";
    exit(0);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
