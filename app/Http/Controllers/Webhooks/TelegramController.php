<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TelegramController extends Controller
{
    public function __construct(private TelegramService $telegram) {}

    public function handle(Request $request): Response
    {
        $update = $request->all();

        SystemLog::log('telegram', 'Webhook received', ['update_id' => $update['update_id'] ?? null]);

        $message = $update['message'] ?? null;

        if (!$message) {
            return response('ok', 200);
        }

        $chatId = $message['chat']['id'] ?? null;
        $text = trim($message['text'] ?? '');
        $from = $message['from'] ?? [];

        if (str_starts_with($text, '/start')) {
            $this->handleStart($chatId, $text, $from);
        } elseif ($text === '/ajuda' || $text === '/help') {
            $this->handleHelp($chatId);
        } elseif ($text === '/status') {
            $this->handleStatus($chatId, $from);
        }

        return response('ok', 200);
    }

    private function handleStart(int $chatId, string $text, array $from): void
    {
        $parts = explode(' ', $text, 2);
        $payload = $parts[1] ?? null;

        if ($payload) {
            // Try to link account via signed token
            $user = User::where('telegram_link_token', $payload)->first();
            if ($user) {
                $user->update([
                    'telegram_id' => (string) $chatId,
                    'telegram_username' => $from['username'] ?? null,
                    'telegram_link_token' => null,
                    'telegram_linked_at' => now(),
                ]);

                $this->telegram->sendMessage($chatId,
                    "✅ <b>Conta vinculada com sucesso!</b>\n\n" .
                    "Olá, {$user->name}! Sua conta SIMOVA FARMA está ativa.\n" .
                    "Você receberá alertas e resumos farmacêuticos aqui.\n\n" .
                    "Digite /ajuda para ver os comandos disponíveis."
                );

                SystemLog::log('telegram', 'Account linked', [
                    'user_id' => $user->id,
                    'chat_id' => $chatId,
                ]);
                return;
            }
        }

        // Generic start
        $this->telegram->sendMessage($chatId,
            "👋 Bem-vindo ao <b>SIMOVA FARMA</b>!\n\n" .
            "Sistema de Inteligência e Monitoramento do Varejo Farmacêutico.\n\n" .
            "Para receber alertas e resumos, assine em nosso site e siga as " .
            "instruções para vincular seu Telegram.\n\n" .
            "🔗 <a href=\"" . config('app.url') . "/assinar\">Assinar agora</a>\n\n" .
            "Se você já é assinante, acesse sua área e copie o link de vinculação."
        );
    }

    private function handleHelp(int $chatId): void
    {
        $this->telegram->sendMessage($chatId,
            "<b>Comandos disponíveis:</b>\n\n" .
            "/start — Iniciar o bot\n" .
            "/status — Ver status da sua assinatura\n" .
            "/ajuda — Este menu de ajuda\n\n" .
            "Para suporte: <a href=\"" . config('app.url') . "/suporte\">Fale conosco</a>"
        );
    }

    private function handleStatus(int $chatId, array $from): void
    {
        $user = User::where('telegram_id', (string) $chatId)->first();

        if (!$user) {
            $this->telegram->sendMessage($chatId,
                "❌ Conta não vinculada.\nAcesse " . config('app.url') . "/obrigado para vincular."
            );
            return;
        }

        $statusLabel = match ($user->status) {
            'active' => '✅ Ativa',
            'test' => '🧪 Teste',
            'pending' => '⏳ Pendente',
            'cancelled' => '❌ Cancelada',
            default => '❓ Desconhecido',
        };

        $this->telegram->sendMessage($chatId,
            "<b>Status da sua assinatura:</b>\n\n" .
            "👤 {$user->name}\n" .
            "📧 {$user->email}\n" .
            "📊 Status: {$statusLabel}\n\n" .
            "Para gerenciar: <a href=\"" . config('app.url') . "/assinar\">Área do assinante</a>"
        );
    }
}
