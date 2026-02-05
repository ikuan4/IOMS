<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TelegramTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test message using the TelegramService';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $service = app()->make(\App\Services\TelegramService::class);
        } catch (\Throwable $e) {
            $this->error('TelegramService not available: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Token prefix: ' . substr(config('services.telegram.bot_token'), 0, 6));
        $this->info('Chat id: ' . config('services.telegram.chat_id'));

        try {
            $resp = $service->sendMessage('Telegram integration test');
            $this->info('Response: ' . json_encode($resp));
        } catch (\Throwable $e) {
            $this->error('Send failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
