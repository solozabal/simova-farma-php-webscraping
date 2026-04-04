<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de integração com a API do Telegram Bot.
 *
 * Responsabilidades:
 *  - Envio de mensagens 1:1 para usuários (chat privado)
 *  - Configuração do webhook
 *  - Processamento de updates recebidos via webhook
 *
 * O token do bot é carregado EXCLUSIVAMENTE do .env (TELEGRAM_BOT_TOKEN).
 * Jamais use valor padrão ou hardcode de token no código.
 *
 * Rate limit da API Telegram:
 *  - Máximo ~30 mensagens/segundo para diferentes usuários
 *  - Use a fila (queue) para envios em massa — não envie de forma síncrona em loop
 */
class TelegramService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $token = config('services.telegram.token');

        if (empty($token)) {
            throw new \RuntimeException(
                'TELEGRAM_BOT_TOKEN não está configurado no .env. ' .
                'Obtenha o token em https://t.me/BotFather e adicione ao .env.'
            );
        }

        $this->token   = $token;
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
    }

    // -------------------------------------------------------------------------
    // Envio de mensagens
    // -------------------------------------------------------------------------

    /**
     * Envia uma mensagem de texto para um chat (usuário ou grupo).
     *
     * @param  string|int $chatId   ID do chat Telegram do destinatário
     * @param  string     $text     Texto da mensagem (suporta MarkdownV2 ou HTML)
     * @param  string     $parseMode 'MarkdownV2' | 'HTML' | '' (nenhum)
     * @return bool  true se enviado com sucesso
     */
    public function sendMessage(string|int $chatId, string $text, string $parseMode = 'HTML'): bool
    {
        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => $parseMode,
        ];

        $response = $this->post('sendMessage', $payload);

        if (! $response->successful()) {
            $error = $response->json('description') ?? 'Erro desconhecido';
            Log::warning("TelegramService: falha ao enviar para {$chatId}: {$error}");
            SystemLog::warning('telegram', "Falha ao enviar mensagem para {$chatId}: {$error}", [
                'chat_id'    => $chatId,
                'error_code' => $response->json('error_code'),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Envia uma mensagem com botões inline (InlineKeyboard).
     *
     * @param  string|int   $chatId
     * @param  string       $text
     * @param  array        $buttons  Array de linhas, cada linha = array de botões
     *                                Ex.: [[['text'=>'Clique', 'url'=>'https://...']]]
     */
    public function sendMessageWithButtons(
        string|int $chatId,
        string $text,
        array $buttons,
        string $parseMode = 'HTML'
    ): bool {
        $payload = [
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => $parseMode,
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons,
            ]),
        ];

        $response = $this->post('sendMessage', $payload);
        return $response->successful();
    }

    // -------------------------------------------------------------------------
    // Configuração do Webhook
    // -------------------------------------------------------------------------

    /**
     * Registra a URL do webhook na API do Telegram.
     * Execute após deploy: php artisan telegram:set-webhook
     *
     * @param  string $url  URL pública do endpoint (ex: https://seusite.com/webhooks/telegram)
     */
    public function setWebhook(string $url): bool
    {
        $response = $this->post('setWebhook', [
            'url'             => $url,
            'allowed_updates' => ['message', 'callback_query'],
        ]);

        return $response->successful();
    }

    /**
     * Remove o webhook registrado.
     */
    public function deleteWebhook(): bool
    {
        $response = $this->post('deleteWebhook', []);
        return $response->successful();
    }

    /**
     * Obtém informações sobre o webhook atual.
     */
    public function getWebhookInfo(): array
    {
        return $this->get('getWebhookInfo');
    }

    // -------------------------------------------------------------------------
    // Informações do bot
    // -------------------------------------------------------------------------

    public function getMe(): array
    {
        return $this->get('getMe');
    }

    // -------------------------------------------------------------------------
    // Internos
    // -------------------------------------------------------------------------

    private function post(string $method, array $payload): Response
    {
        return Http::timeout(15)->post("{$this->baseUrl}/{$method}", $payload);
    }

    private function get(string $method, array $params = []): array
    {
        $response = Http::timeout(15)->get("{$this->baseUrl}/{$method}", $params);
        return $response->json() ?? [];
    }

    // -------------------------------------------------------------------------
    // Templates de mensagem
    // -------------------------------------------------------------------------

    /**
     * Formata uma mensagem de alerta imediato (score >= 8).
     */
    public function formatAlertMessage(
        string $title,
        string $impactLabel,
        string $insight,
        string $url,
        string $sourceLabel = '',
        ?string $publishedAt = null
    ): string {
        $date   = $publishedAt ? "📅 {$publishedAt}" : '📅 Hoje';
        $source = $sourceLabel ? "\n📡 Fonte: <b>{$sourceLabel}</b>" : '';

        return <<<MSG
🚨 <b>ALERTA SIMOVA FARMA</b>

{$date}{$source}
🏷️ <b>{$impactLabel}</b>

<b>{$title}</b>

💡 {$insight}

🔗 <a href="{$url}">Leia mais</a>
MSG;
    }

    /**
     * Formata um bloco de item para o digest diário.
     */
    public function formatDigestItem(
        string $title,
        string $impactLabel,
        string $insight,
        string $url
    ): string {
        return "🔹 <b>{$title}</b>\n"
             . "   {$impactLabel} — {$insight}\n"
             . "   <a href=\"{$url}\">Ler</a>";
    }

    /**
     * Formata a mensagem completa do digest diário.
     *
     * @param  array  $items  Array de strings formatadas por formatDigestItem()
     */
    public function formatDailyDigest(array $items, string $date): string
    {
        $itemsText = implode("\n\n", $items);
        $count     = count($items);

        return <<<MSG
📦 <b>DIGEST SIMOVA FARMA</b> — {$date}
{$count} destaque(s) do dia no varejo farmacêutico:

{$itemsText}

—
🤖 SIMOVA FARMA · Inteligência de Mercado
MSG;
    }
}
