<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

// =============================================================================
// Inventário de páginas do projeto (2025)
// =============================================================================
//
// EXISTENTES:
//   GET /            → Landing page (resources/views/home.blade.php)
//   GET /assinar     → Redireciona para checkout do Mercado Pago (externo)
//   GET /obrigado    → Página de agradecimento pós-pagamento
//                      (resources/views/subscription/obrigado.blade.php)
//   GET /admin       → Painel administrativo Filament
//
// PARCIALMENTE EXISTENTES (estrutura no BD/Filament, sem view pública):
//   Blog/CMS         → Tabela `blog_posts` + recurso Filament em /admin/blog-posts.
//                      Sem controller ou blade público. Criação e edição apenas via admin.
//   Posts Editoriais → Tabela `editorial_posts` + recurso em /admin/editorial-posts.
//                      Entregues via Telegram, sem view pública.
//   Artigos          → Tabela `articles` + recurso em /admin/articles.
//                      Sem view pública — usados para gerar digest e alertas.
//
// NÃO EXISTENTES (sem blade, controller ou rota):
//   Landing page dedicada de vendas → não existe (a home cumpre esse papel)
//   Checkout próprio                → pagamento feito inteiramente no Mercado Pago
//   Páginas jurídicas               → termos de uso, privacidade, reembolso — ausentes
//   Página de suporte/contato       → ausente (link /contato referenciado em obrigado.blade.php
//                                     mas rota/view não criadas)
//   Página de login/cadastro        → autenticação gerenciada pelo Filament (/admin/login)
//
// =============================================================================
// Rotas públicas
// =============================================================================

Route::get('/', [HomeController::class, 'index'])->name('home');

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
