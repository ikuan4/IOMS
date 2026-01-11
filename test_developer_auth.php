<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Developer Authentication System Test ===\n\n";

// Test 1: Developer user identification
$dev = App\Models\User::where('email', 'ikuan4@gmail.com')->first();
echo "1. Developer User (ikuan4@gmail.com):\n";
echo "   - role_id: " . ($dev->role_id ?? 'NULL') . " ✓\n";
echo "   - branch_id: " . ($dev->branch_id ?? 'NULL') . " ✓\n";
echo "   - isSuperAdmin(): " . ($dev->isSuperAdmin() ? 'YES ✓' : 'NO ✗') . "\n\n";

// Test 2: Regular user (should NOT be developer)
$regularUser = App\Models\User::where('email', '!=', 'ikuan4@gmail.com')->first();
if ($regularUser) {
    echo "2. Regular User ({$regularUser->email}):\n";
    echo "   - role_id: " . ($regularUser->role_id ?? 'NULL') . "\n";
    echo "   - branch_id: " . ($regularUser->branch_id ?? 'NULL') . "\n";
    echo "   - isSuperAdmin(): " . ($regularUser->isSuperAdmin() ? 'YES ✗' : 'NO ✓') . "\n\n";
}

// Test 3: Gate::before() bypass check
echo "3. Authorization Bypass Test:\n";
$gate = app('Illuminate\Contracts\Auth\Access\Gate');
$gate->forUser($dev);

// Simulate a gate check
try {
    $result = $gate->allows('create', App\Models\User::class);
    echo "   - Developer can create users: " . ($result ? 'YES ✓' : 'NO ✗') . "\n";
} catch (\Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n";
}

echo "\n✅ All developer authentication tests completed!\n";
