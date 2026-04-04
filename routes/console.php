<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// =============================================================================
// SIMOVA FARMA — Agendador de Tarefas (Scheduler)
// =============================================================================
//
// CONFIGURAÇÃO DO CRON NO HOSTINGER:
// Acesse hPanel → Avançado → Cron Jobs e adicione:
//
//   * * * * * php /home/u804007826/simova-farma/artisan schedule:run >> /dev/null 2>&1
//
// O scheduler do Laravel verificará este arquivo a cada minuto e executará
// apenas as tarefas que estiverem no horário correto.
//
// Fuso horário: America/Sao_Paulo (configurado em config/app.php)
// =============================================================================

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// -----------------------------------------------------------------------------
// Agendamentos SIMOVA FARMA
// -----------------------------------------------------------------------------

// 1. Digest diário — 09:00 America/Sao_Paulo
//    Envia os melhores artigos do dia (score >= 5) para todos os assinantes ativos
Schedule::command('simova:daily-digest')
    ->dailyAt('09:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// 2. Posts editoriais — a cada 5 minutos
//    Verifica e envia posts editoriais aprovados com scheduled_at <= now()
//    Os slots padrão são 09:10 (morning) e 17:40 (afternoon)
Schedule::command('simova:editorial-posts')
    ->everyFiveMinutes()
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// 3. Limpeza semanal de logs antigos (domingos às 23:00)
//    Remove logs com mais de 90 dias
Schedule::call(function () {
    \App\Models\SystemLog::where('created_at', '<', now()->subDays(90))->delete();
})
    ->weekly()
    ->sundays()
    ->at('23:00')
    ->timezone('America/Sao_Paulo')
    ->name('limpar-logs-antigos')
    ->onOneServer();
