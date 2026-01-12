<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check the actual database constraint
$result = DB::select("
    SELECT
        CONSTRAINT_NAME,
        UPDATE_RULE,
        DELETE_RULE
    FROM
        information_schema.REFERENTIAL_CONSTRAINTS
    WHERE
        CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'contract_versions'
        AND CONSTRAINT_NAME LIKE '%contract_id%'
");

echo "=== Foreign Key Constraints on contract_versions ===\n\n";
foreach ($result as $constraint) {
    echo "Constraint: {$constraint->CONSTRAINT_NAME}\n";
    echo "  Update Rule: {$constraint->UPDATE_RULE}\n";
    echo "  Delete Rule: {$constraint->DELETE_RULE}\n";
    echo "\n";
}

if (empty($result)) {
    echo "No foreign key constraints found!\n";
}
