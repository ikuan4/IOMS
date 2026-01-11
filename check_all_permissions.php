<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "All permissions:\n";
$permissions = \Illuminate\Support\Facades\DB::table('permissions')
    ->select('id', 'name', 'slug', 'group')
    ->orderBy('slug')
    ->get();

foreach($permissions as $p) {
    $group = $p->group ?: '(empty)';
    echo "  [{$p->id}] {$p->slug} | group: {$group}\n";
}
