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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'shuftipro' => [
        'client_id' => env('SHUFTI_CLIENT_ID'),
        'secret_key' => env('SHUFTI_SECRET_KEY'),
    ],

    'kuickpay' => [
        'username' => env('KUICKPAY_USERNAME', 'Kuickpaytest'),
        'password' => env('KUICKPAY_PASSWORD', 'Kuickpay@test12'),
        'prefix' => env('KUICKPAY_PREFIX', '01520'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
    ],

    'wise' => [
        'key' => env('WISE_API_KEY'),
        'url' => env('WISE_API_URL', 'https://api.sandbox.transferwise.com'),
        'api_key' => env('WISE_API_KEY'),
        'profile_id' => env('WISE_PROFILE_ID'),
        'base_url' => env('WISE_BASE_URL', 'https://api.sandbox.transferwise.tech'),
    ],

    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', false),
        'site_key' => env('RECAPTCHA_SITE'),
        'secret_key' => env('RECAPTCHA_SECRET'),
    ],

];
