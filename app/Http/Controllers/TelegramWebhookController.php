<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador do webhook do Telegram.
 *
 * Endpoint: POST /webhooks/telegram
 *
 * Fluxo principal: comando /start com token de vinculação
 *   1. Usuário recebe link: https://t.me/BotNome?start=TOKEN
 *   2. Ao clicar e iniciar o bot, o Telegram envia o update com "/start TOKEN"
 *   3. O controller valida o token, vincula telegram_id ao usuário e responde
 *
 * Configuração:
 *   - Registre o webhook: php artisan telegram:set-webhook
 *   - URL: https://seusite.com/webhooks/telegram
 */
class TelegramWebhookController extends Controller
{
    public function __construct(private readonly TelegramService $telegram)
    {
    }

    // -------------------------------------------------------------------------
    // Entrada do webhook
    // -------------------------------------------------------------------------

    /**
     * Recebe e processa um update do Telegram.
     * O Telegram envia um POST para esta URL para cada mensagem/evento.
     */
    public function handle(Request $request): JsonResponse
    {
        $update = $request->json()->all();

        // Log para auditoria (sem dados sensíveis)
        Log::debug('Telegram webhook received', ['update_id' => $update['update_id'] ?? null]);

        try {
            // Mensagem de texto (comando /start, etc.)
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }

            // Callback query (botões inline)
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage(), ['update' => $update]);
        }

        // Sempre retorna 200 para o Telegram não reenviar o update
        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Handlers de mensagens
    // -------------------------------------------------------------------------

    private function handleMessage(array $message): void
    {
        $text   = $message['text'] ?? '';
        $from   = $message['from'] ?? [];
        $chatId = $message['chat']['id'] ?? null;

        if (! $chatId || ! $text) {
            return;
        }

        $telegramId       = (string) ($from['id'] ?? $chatId);
        $telegramUsername = $from['username'] ?? null;
        $firstName        = $from['first_name'] ?? 'assinante';

        // Comando /start (pode trazer token de vinculação: /start TOKEN)
        if (str_starts_with($text, '/start')) {
            $this->handleStartCommand($chatId, $telegramId, $telegramUsername, $firstName, $text);
            return;
        }

        // Comando /ajuda ou /help
        if (in_array($text, ['/ajuda', '/help'], true)) {
            $this->sendHelpMessage($chatId);
            return;
        }

        // Mensagem não reconhecida
        $this->telegram->sendMessage(
            $chatId,
            "Olá! Use /ajuda para ver os comandos disponíveis."
        );
    }

    /**
     * Processa o comando /start, com ou sem token de vinculação.
     *
     * Com token: /start TOKEN → vincula telegram_id ao usuário
     * Sem token:  /start     → orienta o usuário a se cadastrar
     */
    private function handleStartCommand(
        int|string $chatId,
        string $telegramId,
        ?string $username,
        string $firstName,
        string $text
    ): void {
        // Extrai o token da mensagem (/start TOKEN)
        $parts = explode(' ', $text, 2);
        $token = isset($parts[1]) ? trim($parts[1]) : null;

        // Verifica se o Telegram já está vinculado a um usuário
        $existingUser = User::where('telegram_id', $telegramId)->first();

        if ($existingUser) {
            $this->telegram->sendMessage(
                $chatId,
                "👋 Olá, <b>{$firstName}</b>! Sua conta já está vinculada ao SIMOVA FARMA.\n\n" .
                "Use /ajuda para ver os comandos disponíveis."
            );
            return;
        }

        if ($token) {
            $this->handleTokenLinking($chatId, $telegramId, $username, $firstName, $token);
        } else {
            // Sem token: orienta o usuário
            $this->telegram->sendMessage(
                $chatId,
                "👋 Olá, <b>{$firstName}</b>! Bem-vindo ao <b>SIMOVA FARMA</b>.\n\n" .
                "🔗 Para vincular sua conta, acesse:\n" .
                "<a href=\"" . config('app.url') . "/assinar\">Assinar o SIMOVA FARMA</a>\n\n" .
                "Se você já é assinante, acesse seu painel para obter o link de vinculação."
            );
        }
    }

    /**
     * Vincula o Telegram ao usuário usando o token temporário.
     *
     * O token é gerado em UserResource (no painel Filament) ou quando o
     * usuário conclui o pagamento no Mercado Pago.
     */
    private function handleTokenLinking(
        int|string $chatId,
        string $telegramId,
        ?string $username,
        string $firstName,
        string $token
    ): void {
        $user = User::where('telegram_link_token', $token)
            ->where('telegram_link_token_expires_at', '>', now())
            ->first();

        if (! $user) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Link inválido ou expirado.\n\n" .
                "Por favor, acesse o painel de assinante para obter um novo link."
            );
            return;
        }

        // Vincula o Telegram ao usuário
        $user->linkTelegram($telegramId, $username);

        // Se o usuário está pendente, ativa a conta (ele já pagou e agora vinculou)
        if ($user->status === 'pending') {
            $user->activate();
        }

        SystemLog::info('telegram', "Usuário #{$user->id} vinculou o Telegram com sucesso.", [
            'user_id'          => $user->id,
            'telegram_id'      => $telegramId,
            'telegram_username'=> $username,
        ], $user->id);

        $this->telegram->sendMessage(
            $chatId,
            "✅ <b>Conta vinculada com sucesso!</b>\n\n" .
            "Olá, <b>{$firstName}</b>! Você está cadastrado no <b>SIMOVA FARMA</b>.\n\n" .
            "📦 Você receberá:\n" .
            "• Digest diário às 09:00\n" .
            "• Posts editoriais às 09:10 e 17:40\n" .
            "• Alertas imediatos para notícias com alto impacto\n\n" .
            "Use /ajuda para ver os comandos disponíveis. Boas vendas! 💊"
        );
    }

    private function sendHelpMessage(int|string $chatId): void
    {
        $this->telegram->sendMessage(
            $chatId,
            "<b>SIMOVA FARMA — Comandos</b>\n\n" .
            "/start — Iniciar ou vincular conta\n" .
            "/ajuda — Exibir esta mensagem de ajuda\n\n" .
            "Em caso de dúvidas, entre em contato pelo site:\n" .
            "<a href=\"" . config('app.url') . "/contato\">Suporte</a>"
        );
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        // Reservado para futuros botões inline
        $callbackId = $callbackQuery['id'] ?? null;
        if ($callbackId) {
            // Confirma recebimento do callback (evita "loading" no botão)
            $this->telegram->sendMessage(
                $callbackQuery['message']['chat']['id'] ?? '',
                'Comando não reconhecido.'
            );
        }
    }
}
