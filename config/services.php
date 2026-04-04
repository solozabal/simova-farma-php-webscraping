<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    // -------------------------------------------------------------------------
    // Telegram Bot
    // -------------------------------------------------------------------------

    'telegram' => [
        // Token do bot — obtido em https://t.me/BotFather
        // NUNCA commitar — definir no .env como TELEGRAM_BOT_TOKEN
        'token'        => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    ],

    // -------------------------------------------------------------------------
    // Mercado Pago
    // -------------------------------------------------------------------------

    'mercadopago' => [
        // Credenciais obtidas em https://www.mercadopago.com.br/developers/panel
        // NUNCA commitar — definir no .env
        'access_token'   => env('MERCADO_PAGO_ACCESS_TOKEN'),
        'public_key'     => env('MERCADO_PAGO_PUBLIC_KEY'),
        'webhook_secret' => env('MERCADO_PAGO_WEBHOOK_SECRET'),
        'plan_id'        => env('MERCADO_PAGO_PLAN_ID'),
    ],

    // -------------------------------------------------------------------------
    // GNews API
    // -------------------------------------------------------------------------

    'gnews' => [
        'api_key' => env('GNEWS_API_KEY'),
    ],

    // -------------------------------------------------------------------------
    // IA (DeepSeek / OpenAI)
    // -------------------------------------------------------------------------

    'ai' => [
        'provider' => env('AI_PROVIDER', 'deepseek'),
        'api_key'  => env('AI_API_KEY'),
    ],



    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
