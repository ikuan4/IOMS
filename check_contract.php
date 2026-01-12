<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$contractId = 35;

echo "Checking Contract ID: $contractId\n";
echo "========================================\n";

$contract = App\Models\Contract::withTrashed()->find($contractId);

if ($contract) {
    echo "Contract exists: YES\n";
    echo "Deleted at: " . ($contract->deleted_at ?? 'NULL (not deleted)') . "\n";
    echo "Contract number: " . $contract->contract_number . "\n";

    $versions = App\Models\ContractVersion::withTrashed()->where('contract_id', $contractId)->get();
    echo "\nVersions count: " . $versions->count() . "\n";

    foreach ($versions as $version) {
        echo "  Version {$version->version_number}: " . ($version->deleted_at ? 'DELETED' : 'ACTIVE') . "\n";
    }

    $activeVersions = $versions->whereNull('deleted_at');
    echo "\nActive versions count: " . $activeVersions->count() . "\n";
} else {
    echo "Contract NOT FOUND\n";
}
