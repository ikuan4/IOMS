<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Restore all soft-deleted contracts
$deletedContracts = App\Models\Contract::onlyTrashed()->get();

echo "Found " . $deletedContracts->count() . " soft-deleted contracts\n";

foreach ($deletedContracts as $contract) {
    echo "Restoring Contract ID {$contract->id} (#{$contract->contract_number})...\n";
    $contract->restore();
}

echo "\nDone! All contracts restored.\n";
