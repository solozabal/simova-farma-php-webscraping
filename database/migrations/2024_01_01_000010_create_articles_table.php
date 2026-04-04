<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de artigos/notícias coletados das fontes (RSS, APIs).
 * Cada artigo passa por normalização, deduplicação e score antes de ser entregue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            // Hash único: sha256(normalize(title) + canonical_url)
            // Usado para deduplicação — não inserir se o hash já existe
            $table->string('hash', 64)->unique();

            $table->string('title');
            $table->text('content')->nullable();       // Resumo/excerpt do artigo
            $table->string('url');                     // URL original da fonte
            $table->string('source_key');              // Chave da fonte (ex: anvisa, uol_economia)
            $table->string('source_label')->nullable();// Nome legível da fonte

            // Classificação
            $table->enum('category', [
                'regulamentacao',
                'varejo',
                'tecnologia',
                'marketing',
                'geral',
            ])->default('geral');

            $table->enum('language', ['pt', 'en'])->default('pt');

            // Score de relevância (0-10)
            // >= 8: alerta imediato; 5-7: digest diário; < 5: apenas armazenado
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('impact_label')->nullable(); // Rótulo curto para o Telegram

            // Texto de insight gerado pela IA (ou fallback)
            $table->text('insight')->nullable();

            // Controle de entrega
            $table->boolean('alert_sent')->default(false);
            $table->boolean('digest_sent')->default(false);
            $table->boolean('blog_published')->default(false);

            $table->timestamp('published_at')->nullable(); // Data original de publicação na fonte
            $table->timestamps();

            $table->index(['score', 'digest_sent']);
            $table->index(['score', 'alert_sent']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
