<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de logs do sistema — auditoria de operações importantes.
 * Registra eventos como: envios Telegram, webhooks recebidos, ativações, erros.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();

            // Nível do log: info, warning, error, debug
            $table->string('level', 20)->default('info');

            // Canal de origem (ex: telegram, mercadopago, scraper, scheduler)
            $table->string('channel', 50)->default('app');

            // Mensagem principal
            $table->text('message');

            // Dados extras em JSON (payload, contexto, stack trace resumido)
            $table->json('context')->nullable();

            // Referência ao usuário relacionado (se aplicável)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // IP e user-agent (para webhooks e ações web)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['channel', 'level']);
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
