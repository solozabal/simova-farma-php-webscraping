<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * Comando para registrar/atualizar o webhook do Telegram.
 *
 * Uso:
 *   php artisan telegram:set-webhook
 *
 * Execute este comando UMA VEZ após o deploy para registrar
 * a URL do webhook na API do Telegram.
 *
 * Pré-requisitos:
 *   - TELEGRAM_BOT_TOKEN configurado no .env
 *   - APP_URL configurado com a URL pública (HTTPS) do site
 *
 * O webhook será registrado em: {APP_URL}/webhooks/telegram
 */
class SetTelegramWebhookCommand extends Command
{
    protected $signature   = 'telegram:set-webhook {--delete : Remove o webhook em vez de configurar}';
    protected $description = 'Registra ou remove o webhook do bot Telegram';

    public function handle(TelegramService $telegram): int
    {
        if ($this->option('delete')) {
            $success = $telegram->deleteWebhook();
            if ($success) {
                $this->info('[Telegram] Webhook removido com sucesso.');
            } else {
                $this->error('[Telegram] Falha ao remover webhook.');
            }
            return $success ? self::SUCCESS : self::FAILURE;
        }

        $webhookUrl = rtrim(config('app.url'), '/') . '/webhooks/telegram';
        $this->info("[Telegram] Registrando webhook em: {$webhookUrl}");

        $success = $telegram->setWebhook($webhookUrl);

        if ($success) {
            $this->info('[Telegram] Webhook registrado com sucesso!');
            $this->line("URL: {$webhookUrl}");

            // Exibe informações do webhook registrado
            $info = $telegram->getWebhookInfo();
            $this->line("Status: " . ($info['result']['url'] ?? 'N/A'));
        } else {
            $this->error('[Telegram] Falha ao registrar webhook. Verifique TELEGRAM_BOT_TOKEN e APP_URL.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
