<?php

namespace App\Console\Commands;

use App\Jobs\SendEditorialPostsJob;
use Illuminate\Console\Command;

/**
 * Comando para disparar o envio de posts editoriais via fila.
 *
 * Uso:
 *   php artisan simova:editorial-posts
 *
 * Este comando é executado pelo scheduler a cada 5 minutos.
 * Ele verifica posts editoriais com status='approved' e scheduled_at <= now()
 * e os envia para todos os usuários ativos via Telegram.
 *
 * Slots padrão:
 *   - 09:10 America/Sao_Paulo → slot morning
 *   - 17:40 America/Sao_Paulo → slot afternoon
 */
class SendEditorialPostsCommand extends Command
{
    protected $signature   = 'simova:editorial-posts';
    protected $description = 'Verifica e envia posts editoriais aprovados via Telegram';

    public function handle(): int
    {
        $this->info('[SIMOVA FARMA] Despachando SendEditorialPostsJob...');

        SendEditorialPostsJob::dispatch()->onQueue('telegram');

        $this->info('[SIMOVA FARMA] Job despachado!');

        return self::SUCCESS;
    }
}
