<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_posts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('fixed'); // fixed|auto_digest|auto_alert_summary|manual
            $table->string('title')->nullable();
            $table->string('title_template')->nullable();
            $table->text('content')->nullable();
            $table->text('content_template')->nullable();
            $table->string('schedule_rule')->nullable(); // cron expression or named rule
            $table->timestamp('publish_at')->nullable();
            $table->string('channel')->default('telegram'); // telegram|blog|both
            $table->string('status')->default('active'); // active|paused
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_posts');
    }
};
