<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    protected $signature = 'simova:telegram:set-webhook';
    protected $description = 'Register the Telegram webhook URL with the bot';

    public function handle(TelegramService $telegram): int
    {
        $url = config('app.url') . '/webhooks/telegram';
        $this->info("Registering webhook: {$url}");

        $result = $telegram->setWebhook($url);

        if ($result['ok'] ?? false) {
            $this->info('Webhook registered successfully!');
            return self::SUCCESS;
        }

        $this->error('Failed to register webhook: ' . ($result['description'] ?? 'Unknown error'));
        return self::FAILURE;
    }
}
