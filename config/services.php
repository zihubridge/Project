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

    'stellar' => [
        'horizon_url' => env('ENVIRONMENT') === 'public'
            ? env('STELLAR_HORIZON_MAINNET')
            : env('STELLAR_HORIZON_TESTNET'),

        'wallet' => env('ENVIRONMENT') === 'public'
            ? env('STELLAR_PUBLIC_ADDRESS')
            : env('STELLAR_TESTNET_PUBLIC_ADDRESS'),

        'seed' => env('ENVIRONMENT') === 'public'
            ? env('STELLAR_SECRET_KEY')
            : env('STELLAR_TESTNET_SECRET_KEY'),
    ],

    'xrpl' => [
        'rpc' => env('ENVIRONMENT') === 'public'
            ? env('XRPL_RPC_MAINNET')
            : env('XRPL_RPC_TESTNET'),

        'wallet' => env('ENVIRONMENT') === 'public'
            ? env('XRPL_PUBLIC_ADDRESS')
            : env('XRPL_TESTNET_PUBLIC_ADDRESS'),

        'seed' => env('ENVIRONMENT') === 'public'
            ? env('XRPL_SECRET_KEY')
            : env('XRPL_TESTNET_SECRET_KEY'),
    ],

    'changenow' => [
        'base_url' => env('CHANGENOW_BASE_URL', 'https://api.changenow.io'),
        'api_key'  => env('CHANGENOW_API_KEY'),
    ],

];
