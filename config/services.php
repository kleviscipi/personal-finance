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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'exchangerate_host' => [
        'base_url' => env('EXCHANGERATE_HOST_BASE_URL', 'https://api.exchangerate.host'),
        'access_key' => env('EXCHANGERATE_HOST_ACCESS_KEY'),
        'endpoint' => env('EXCHANGERATE_HOST_ENDPOINT', 'latest'),
        'sync_base' => env('EXCHANGERATE_HOST_SYNC_BASE', 'ALL'),
        'sync_symbols' => env('EXCHANGERATE_HOST_SYNC_SYMBOLS', 'USD,EUR'),
        'sync_time' => env('EXCHANGERATE_HOST_SYNC_TIME', '02:00'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
