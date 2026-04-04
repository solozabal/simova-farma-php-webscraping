<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de posts do blog público.
 * Os posts são criados como rascunhos (draft) e publicados apenas após aprovação manual.
 * NÃO há publicação automática sem aprovação de um administrador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();          // URL amigável: gerada a partir do título
            $table->longText('content');               // Conteúdo HTML/Markdown do post
            $table->text('excerpt')->nullable();       // Resumo para listagem/SEO

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            // Classificação
            $table->string('category')->nullable();
            $table->json('tags')->nullable();           // Array de tags

            // Imagem de capa
            $table->string('featured_image')->nullable();

            // Autoria
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            // Status: draft=rascunho, published=publicado, archived=arquivado
            // IMPORTANTE: posts só vão para 'published' com aprovação manual no admin
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            // Publicação programada (só efetiva após status=published manualmente)
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
