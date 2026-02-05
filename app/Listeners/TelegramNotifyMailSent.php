<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TelegramNotifyMailSent implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            $telegram = app()->make(\App\Services\TelegramService::class);
        } catch (\Throwable $e) {
            logger()->warning('Telegram service not available: ' . $e->getMessage());
            return;
        }

        // Try to extract subject and body with several fallbacks to support different mailer implementations
        $subject = '';
        $bodyHtml = '';

        $message = $event->message;

        if (method_exists($message, 'getSubject')) {
            $val = $message->getSubject();
            if (is_string($val)) {
                $subject = $val;
            } elseif (is_object($val) && method_exists($val, '__toString')) {
                $subject = (string) $val;
            }
        }

        // Symfony Mailer: getHtmlBody / getTextBody
        if (method_exists($message, 'getHtmlBody')) {
            $val = $message->getHtmlBody();
            if (is_string($val)) {
                $bodyHtml = $val;
            } elseif (is_object($val) && method_exists($val, '__toString')) {
                $bodyHtml = (string) $val;
            }
        }

        // SwiftMailer or generic: getBody()
        if (empty($bodyHtml) && method_exists($message, 'getBody')) {
            $val = $message->getBody();
            if (is_string($val)) {
                $bodyHtml = $val;
            } elseif (is_object($val) && method_exists($val, '__toString')) {
                $bodyHtml = (string) $val;
            }
        }

        // If the message has children parts, search for html part
        if (empty($bodyHtml) && property_exists($message, 'children') && is_iterable($message->children)) {
            foreach ($message->children as $part) {
                if (is_object($part) && method_exists($part, 'getContentType')) {
                    $type = $part->getContentType();
                    if (is_string($type) && Str::contains($type, 'html')) {
                        if (method_exists($part, 'getBody')) {
                            $val = $part->getBody();
                            if (is_string($val)) {
                                $bodyHtml = $val;
                                break;
                            } elseif (is_object($val) && method_exists($val, '__toString')) {
                                $bodyHtml = (string) $val;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Last resort: cast message to string if possible
        if (empty($bodyHtml)) {
            if (is_string($message)) {
                $bodyHtml = $message;
            } elseif (is_object($message) && method_exists($message, '__toString')) {
                $bodyHtml = (string) $message;
            } else {
                $bodyHtml = '';
            }
        }

        // Compose a concise Telegram-friendly message: subject only and a short note.
        $text = !empty($subject) ? $subject : 'Notification';
        $text .= "\n\n(Details sent by email)";

        try {
            $response = $telegram->sendMessage($text);
            if (!isset($response['ok']) || $response['ok'] !== true) {
                logger()->warning('Telegram API returned error for mail notify', ['response' => $response]);
            }
        } catch (\Throwable $e) {
            logger()->warning('Telegram notify failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
