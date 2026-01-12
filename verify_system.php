<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== IOMS Contract & Version System Verification ===\n\n";

// Find any contract
$contract = App\Models\Contract::withCount('versions')->first();

if (!$contract) {
    echo "❌ No contracts with multiple versions found. Creating test data...\n";
    exit(1);
}

echo "✓ Found Contract #{$contract->id}: {$contract->contract_number}\n";
echo "  Branch: {$contract->branch->name}\n";
echo "  Type: {$contract->contractType->name}\n";
echo "  Active: " . ($contract->is_active ? 'Yes' : 'No') . "\n";
echo "  Versions count: " . $contract->versions()->count() . "\n\n";

// Load versions
$versions = $contract->versions()->withTrashed()->orderBy('version_number')->get();

echo "--- Versions ---\n";
foreach ($versions as $version) {
    $status = $version->deleted_at ? '❌ DELETED' : '✓ ACTIVE';
    $latest = $version->isLatest() ? ' [LATEST]' : '';
    echo "  Version {$version->version_number}: {$status}{$latest}\n";
    echo "    Start: {$version->start_date->format('Y-m-d')}\n";
    echo "    End: {$version->end_date->format('Y-m-d')}\n";
}

// Test latestVersion relationship
echo "\n--- Latest Version Test ---\n";
$latestVersion = $contract->latestVersion;
if ($latestVersion) {
    echo "✓ latestVersion works: Version {$latestVersion->version_number}\n";
    echo "  Deleted: " . ($latestVersion->deleted_at ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ latestVersion is NULL\n";
}

// Test status calculation
echo "\n--- Status Calculation ---\n";
echo "Contract Status: {$contract->status}\n";

// Test contract is not deleted
echo "\n--- Contract Integrity ---\n";
echo "Contract deleted_at: " . ($contract->deleted_at ?? 'NULL (not deleted)') . "\n";

if ($contract->deleted_at) {
    echo "❌ CONTRACT IS SOFT-DELETED!\n";
} else {
    echo "✓ Contract is NOT deleted\n";
}

// Check foreign key constraint
echo "\n--- Foreign Key Constraint ---\n";
$result = DB::select('SHOW CREATE TABLE contract_versions');
$createTable = $result[0]->{'Create Table'};

if (strpos($createTable, 'ON DELETE CASCADE') !== false && strpos($createTable, 'contract_id') !== false) {
    echo "❌ PROBLEM: Foreign key still has CASCADE ON DELETE\n";
} elseif (strpos($createTable, 'contract_id') !== false) {
    echo "✓ Foreign key constraint is correct (RESTRICT is default when no ON DELETE clause)\n";
} else {
    echo "⚠ Foreign key configuration unclear\n";
}

echo "\n=== Verification Complete ===\n";
