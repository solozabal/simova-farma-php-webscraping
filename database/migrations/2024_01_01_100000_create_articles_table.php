<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('hash')->unique();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('url');
            $table->string('source_key');
            $table->string('category')->default('geral'); // regulamentacao|varejo|tecnologia|marketing|geral|macro
            $table->string('language')->default('pt'); // pt|en
            $table->string('impact_label')->nullable();
            $table->decimal('score', 4, 1)->default(0);
            $table->text('insight')->nullable();
            $table->timestamp('published_at')->nullable();
            // Editorial status
            $table->string('editorial_status')->default('queued'); // queued|approved|rejected|published
            // Delivery flags
            $table->boolean('is_sent_alert')->default(false);
            $table->boolean('is_sent_digest')->default(false);
            $table->boolean('is_blog_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
