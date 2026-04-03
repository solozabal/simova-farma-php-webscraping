<?php

use App\Http\Controllers\Webhooks\MercadoPagoController;
use App\Http\Controllers\Webhooks\TelegramController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes (excluded from CSRF)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/mercadopago', [MercadoPagoController::class, 'handle'])
    ->name('webhooks.mercadopago');

Route::post('/webhooks/telegram', [TelegramController::class, 'handle'])
    ->name('webhooks.telegram');
