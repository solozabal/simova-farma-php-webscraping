<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Papel do usuário no sistema
            $table->enum('role', ['admin', 'subscriber'])->default('subscriber');

            // Status da assinatura
            // lead=interessado, pending=pagamento pendente, active=assinante ativo,
            // cancelled=cancelado, test=usuário de teste interno
            $table->enum('status', ['lead', 'pending', 'active', 'cancelled', 'test'])->default('lead');

            // Vinculação com o Telegram
            $table->string('telegram_id')->nullable()->unique();
            $table->string('telegram_username')->nullable();
            $table->timestamp('telegram_linked_at')->nullable();

            // Token temporário para vincular a conta ao Telegram via /start
            $table->string('telegram_link_token')->nullable()->unique();
            $table->timestamp('telegram_link_token_expires_at')->nullable();

            // Identificadores do provedor de pagamento
            $table->string('payment_provider')->nullable();          // ex: mercadopago
            $table->string('payment_customer_id')->nullable();       // ID do cliente no MP
            $table->string('payment_subscription_id')->nullable();   // ID da assinatura recorrente

            // Datas importantes
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
