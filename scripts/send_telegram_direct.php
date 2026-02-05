<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = config('services.telegram.bot_token');
$chatId = config('services.telegram.chat_id');

if (empty($token) || empty($chatId)) {
    echo "Telegram token or chat_id not configured in config/services.php\n";
    exit(1);
}

$url = "https://api.telegram.org/bot{$token}/sendMessage";

$data = [
    'chat_id' => (int) $chatId,
    'text' => 'Direct test from scripts/send_telegram_direct.php',
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$resp = curl_exec($ch);
if ($resp === false) {
    echo 'cURL error: ' . curl_error($ch) . PHP_EOL;
    exit(1);
}
curl_close($ch);

echo $resp . PHP_EOL;
