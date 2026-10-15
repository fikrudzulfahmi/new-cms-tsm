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

    // TSM Superapp: API aplikasi Data Induk Siswa (token diisi di .env, jangan di-commit)
    'induk' => [
        'url' => env('INDUK_API_URL'),
        'token' => env('INDUK_API_TOKEN'),
        'timeout' => (int) env('INDUK_API_TIMEOUT', 20),
        'jurusan' => env('INDUK_API_JURUSAN'), // opsional: saring kelas, mis. "Sepeda Motor"
    ],

    // Google reCAPTCHA v2 (checkbox) untuk login siswa. Kosong = captcha dimatikan.
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
