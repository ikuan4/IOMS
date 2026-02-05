<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('services.telegram.bot_token');
if (empty($token)) {
	echo "Telegram bot token not configured in config/services.php\n";
	exit(1);
}

$url = "https://api.telegram.org/bot{$token}/getUpdates";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
if ($resp === false) {
	echo 'cURL error: ' . curl_error($ch) . PHP_EOL;
	exit(1);
}
curl_close($ch);

echo $resp;
