<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();


// Use the Mail facade to send a raw message which will trigger MessageSent
use Illuminate\Support\Facades\Mail;

Mail::raw('Telegram mirror test body', function ($m) {
    $m->to('test@example.com');
    $m->subject('Test Telegram Notification');
});

echo "Test mail dispatched.\n";
