<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    protected string $token;
    protected string $chatId;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');

        if (empty($this->token) || empty($this->chatId)) {
            throw new \InvalidArgumentException('Telegram bot token or chat_id is not configured in config/services.php');
        }

        // basic runtime guard: token format is <bot_id>:<rest>
        if (!str_contains($this->token, ':')) {
            throw new \InvalidArgumentException('Telegram bot token appears malformed');
        }
        $botId = explode(':', $this->token, 2)[0];
        if ((string)$this->chatId === (string)$botId) {
            throw new \InvalidArgumentException('Telegram chat_id matches bot id — likely misconfigured');
        }

        logger()->info('TelegramService initialized', [
            'token_prefix' => substr($this->token, 0, 6),
            'chat_id' => $this->chatId,
        ]);
    }

    /**
     * Send a plain-text message to the configured chat.
     * Returns decoded JSON response from Telegram API.
     *
     * @param string $text
     * @return array<string,mixed>|null
     */
    public function sendMessage(string $text): ?array
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";

        logger()->info('Telegram send', [
            'token_prefix' => substr($this->token, 0, 6),
            'chat_id' => $this->chatId,
            'text_length' => mb_strlen($text),
        ]);

        // Telegram max message length is 4096 characters. Truncate if necessary.
        $max = 4096;
        if (mb_strlen($text) > $max) {
            $originalLen = mb_strlen($text);
            $text = mb_substr($text, 0, $max - 20) . "\n\n(Truncated)";
            logger()->warning('Telegram message truncated', ['original_length' => $originalLen, 'truncated_length' => mb_strlen($text)]);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'chat_id' => (int) $this->chatId,
            'text' => $text,
        ]);

        $decoded = null;
        try {
            $decoded = $response->json();
        } catch (\Throwable $e) {
            logger()->warning('Telegram response JSON decode failed', ['exception' => $e, 'body' => (string)$response->body()]);
        }

        if ($response->failed()) {
            logger()->warning('Telegram API call failed', ['status' => $response->status(), 'response' => $decoded]);
            $err = '';
            if (is_array($decoded)) {
                $err = ($decoded['error_code'] ?? '') ? ("error_code=" . ($decoded['error_code'] ?? '') . "; ") : '';
                $err .= ($decoded['description'] ?? $response->body());
            } else {
                $err = $response->body();
            }
            throw new \RuntimeException('Telegram API returned error: ' . $err);
        }

        return $decoded;
    }
}
