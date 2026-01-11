<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Permissions by group:\n";
$groups = \Illuminate\Support\Facades\DB::table('permissions')
    ->select('group', \Illuminate\Support\Facades\DB::raw('count(*) as cnt'))
    ->groupBy('group')
    ->orderBy('group')
    ->get();

foreach($groups as $g) {
    echo "  " . $g->group . ": " . $g->cnt . " permissions\n";
}

echo "\nTotal permissions: " . \Illuminate\Support\Facades\DB::table('permissions')->count() . "\n";
