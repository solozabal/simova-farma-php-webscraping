<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyDigestJob;
use Illuminate\Console\Command;

/**
 * Comando para disparar o digest diário via fila.
 *
 * Uso:
 *   php artisan simova:daily-digest
 *
 * Este comando é executado pelo scheduler às 09:00 (America/Sao_Paulo).
 * Ele simplesmente despacha o job SendDailyDigestJob para a fila,
 * que seleciona artigos elegíveis e envia para todos os usuários ativos.
 */
class SendDailyDigestCommand extends Command
{
    protected $signature   = 'simova:daily-digest {--force : Força o envio mesmo fora do horário}';
    protected $description = 'Envia o digest diário de artigos via Telegram para assinantes ativos';

    public function handle(): int
    {
        $this->info('[SIMOVA FARMA] Despachando SendDailyDigestJob...');

        SendDailyDigestJob::dispatch()->onQueue('telegram');

        $this->info('[SIMOVA FARMA] Job despachado com sucesso!');
        $this->line('Os usuários elegíveis receberão o digest em breve via Telegram.');

        return self::SUCCESS;
    }
}
