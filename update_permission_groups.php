<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Updating permission groups based on slug prefixes...\n\n";

$permissions = DB::table('permissions')->select('id', 'slug', 'group')->get();

$updated = 0;
foreach($permissions as $perm) {
    if ($perm->group) {
        continue; // Skip if already has a group
    }

    $slug = $perm->slug;

    // Extract module from slug (everything before the last dot)
    $parts = explode('.', $slug);

    if (count($parts) >= 2) {
        // For slugs like "users.view", "contracts.versions.create"
        // We want the main module (users, contracts, etc.)
        $module = $parts[0];

        // Map to display group names
        $groupMap = [
            'users' => 'users',
            'roles' => 'roles',
            'branches' => 'branches',
            'permissions' => 'permissions',
            'contract-types' => 'contract-types',
            'contracts' => 'contracts',
            'notification-recipients' => 'notifications', // map to notifications group
        ];

        $group = $groupMap[$module] ?? $module;

        DB::table('permissions')
            ->where('id', $perm->id)
            ->update(['group' => $group]);

        echo "  [{$perm->id}] $slug => group: $group\n";
        $updated++;
    }
}

echo "\n✓ Updated $updated permissions with group field.\n";

// Show summary
echo "\nPermissions by group:\n";
$groups = DB::table('permissions')
    ->select('group', DB::raw('count(*) as cnt'))
    ->groupBy('group')
    ->orderBy('group')
    ->get();

foreach($groups as $g) {
    $groupName = $g->group ?: '(empty)';
    echo "  $groupName: {$g->cnt} permissions\n";
}
