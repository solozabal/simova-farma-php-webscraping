<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de posts editoriais programados para envio via Telegram.
 * O calendário editorial define os horários de envio (09:10 e 17:40).
 * Cada registro é um post que será enviado no slot definido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_posts', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('content');               // Conteúdo/template da mensagem Telegram
            $table->text('content_fallback')->nullable(); // Texto de fallback caso a IA falhe

            // Slot editorial: morning (09:10) ou afternoon (17:40)
            $table->enum('slot', ['morning', 'afternoon'])->default('morning');

            // Data/hora programada para envio
            $table->timestamp('scheduled_at');

            // Status do post
            // draft=rascunho, approved=aprovado para envio, sent=enviado, cancelled=cancelado
            $table->enum('status', ['draft', 'approved', 'sent', 'cancelled'])->default('draft');

            // Rastreamento de envio
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0); // Quantos usuários receberam

            // Usuário admin que aprovou
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Referência ao artigo de origem (opcional)
            $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index('slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_posts');
    }
};
