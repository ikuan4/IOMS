<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$tables = [
    'users',
    'roles',
    'branches',
    'permissions',
    'audit_logs',
    'contracts',
    'contract_types',
    'contract_versions',
    'contract_version_files',
    'notification_recipients',
    'contract_reminders',
    'stored_files',

    // spatie/permission pivot tables
    'role_has_permissions',
    'model_has_permissions',
    'model_has_roles',
];

$requiredDefault = [
    'created_at',
    'updated_at',
    'deleted_at',
    'created_by',
    'updated_by',
    'deleted_by',
    'restored_by',
    'restored_at',
];

// Exempt tables (e.g., Spatie pivot tables) from full audit-field requirements.
// These are typically managed by the permission package and don't need deleted/restored metadata.
$requiredByTable = [
    'role_has_permissions' => ['created_at', 'updated_at'],
    'model_has_permissions' => ['created_at', 'updated_at'],
    'model_has_roles' => ['created_at', 'updated_at'],
];

$out = [];
foreach ($tables as $table) {
    $isExempt = array_key_exists($table, $requiredByTable);
    $required = $requiredByTable[$table] ?? $requiredDefault;

    try {
        if (!Schema::hasTable($table)) {
            $out[$table] = [
                'has_table' => false,
                'exempt' => $isExempt,
                'required' => $required,
                'missing' => $required,
            ];
            continue;
        }

        $columns = Schema::getColumnListing($table);
        $missing = array_values(array_diff($required, $columns));

        $out[$table] = [
            'has_table' => true,
            'exempt' => $isExempt,
            'required' => $required,
            'missing' => $missing,
        ];
    } catch (Throwable $e) {
        $out[$table] = [
            'has_table' => false,
            'exempt' => $isExempt,
            'required' => $required,
            'missing' => $required,
            'error' => $e->getMessage(),
        ];
    }
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
