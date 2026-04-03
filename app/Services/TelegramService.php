<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class TelegramService
{
    private string $token;
    private string $apiBase;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token')
            ?? throw new \RuntimeException('TELEGRAM_BOT_TOKEN is not set.');
        $this->apiBase = "https://api.telegram.org/bot{$this->token}";
    }

    /**
     * Send a message to a single chat ID with rate limiting.
     */
    public function sendMessage(string|int $chatId, string $text, array $options = []): array
    {
        $key = "telegram_send:{$chatId}";

        // 1 message per second per chat_id (Telegram limit)
        if (RateLimiter::tooManyAttempts($key, 1)) {
            sleep(1);
        }
        RateLimiter::hit($key, 1);

        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options);

        $response = Http::timeout(10)->post("{$this->apiBase}/sendMessage", $payload);

        if (!$response->successful()) {
            SystemLog::log('telegram', 'Failed to send message', [
                'chat_id' => $chatId,
                'error' => $response->body(),
            ], 'error');
        }

        return $response->json() ?? [];
    }

    /**
     * Set the webhook URL for this bot.
     */
    public function setWebhook(string $url): array
    {
        $response = Http::post("{$this->apiBase}/setWebhook", [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query'],
        ]);
        return $response->json() ?? [];
    }

    /**
     * Generate a deep link to the bot with a payload.
     */
    public function deepLink(string $payload): string
    {
        $botUsername = config('services.telegram.bot_username', '');
        return "https://t.me/{$botUsername}?start={$payload}";
    }
}
