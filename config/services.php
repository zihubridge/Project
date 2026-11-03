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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    /*
    |--------------------------------------------------------------------------
    | Cross-Chain Swap Execution Providers
    |--------------------------------------------------------------------------
    */
    'swap' => [
        'default_provider' => env('SWAP_DEFAULT_PROVIDER', 'near_intents'),
        'quote_ttl_minutes' => (int) env('NEAR_INTENTS_QUOTE_TTL_MINUTES', 15),
    ],

    'near_intents' => [
        'base_url' => rtrim((string) env('NEAR_INTENTS_BASE_URL', 'https://1click.chaindefuser.com'), '/'),
        'api_key' => env('NEAR_INTENTS_API_KEY', env('NEAR_INTENTS_JWT')),
        'partner_fee_bps' => (int) env('NEAR_INTENTS_PARTNER_FEE_BPS', 0),
        'partner_fee_recipient' => env('NEAR_INTENTS_PARTNER_FEE_RECIPIENT'),
        'timeout_seconds' => (int) env('NEAR_INTENTS_TIMEOUT_SECONDS', 20),
        'referral' => env('NEAR_INTENTS_REFERRAL', 'zihubridge'),
    ],

];
