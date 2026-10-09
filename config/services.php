<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Yandex AI Studio: API-ключ сервисного аккаунта с ролью ai.languageModels.user и id каталога.
    'yandexgpt' => [
        'api_key' => env('YANDEXGPT_API_KEY'),
        'folder_id' => env('YANDEXGPT_FOLDER_ID'),
        'model' => env('YANDEXGPT_MODEL', 'yandexgpt-lite/latest'),
        'temperature' => 0.3,
        'max_tokens' => 1000,
        'timeout' => 30,
    ],

    // GigaChat API: ключ авторизации (Basic) из личного кабинета Сбера.
    'gigachat' => [
        'auth_key' => env('GIGACHAT_AUTH_KEY'),
        'scope' => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'),
        'model' => env('GIGACHAT_MODEL', 'GigaChat'),
        'embedding_model' => env('GIGACHAT_EMBEDDING_MODEL', 'Embeddings'),
        // Сертификат НУЦ Минцифры (russian_trusted_root_ca.cer) — без него TLS не проходит.
        'ca_bundle' => env('GIGACHAT_CA_BUNDLE'),
        'temperature' => 0.3,
        'max_tokens' => 1000,
        'timeout' => 30,
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
