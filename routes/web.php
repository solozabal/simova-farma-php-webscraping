<?php

use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// Rotas públicas
// =============================================================================

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Assinatura — redireciona para o checkout do Mercado Pago
Route::get('/assinar', [SubscriptionController::class, 'assinar'])->name('assinar');

// Página de agradecimento após o pagamento
Route::get('/obrigado', [SubscriptionController::class, 'obrigado'])->name('obrigado');

// =============================================================================
// Webhooks — sem CSRF (os provedores externos não enviam tokens CSRF)
// =============================================================================

// Webhook do Telegram (recebe updates do bot)
// Configurar via: php artisan telegram:set-webhook
Route::post('/webhooks/telegram', [TelegramWebhookController::class, 'handle'])
    ->name('webhooks.telegram')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Webhook do Mercado Pago (recebe eventos de pagamento/assinatura)
// Configurar no painel: https://www.mercadopago.com.br/developers/panel
Route::post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle'])
    ->name('webhooks.mercadopago')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
