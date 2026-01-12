<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$result = DB::select('SHOW CREATE TABLE contract_versions');
echo $result[0]->{'Create Table'};
echo "\n";
