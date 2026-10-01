<?php

return [

    'homis' => [
        'enabled' => filter_var(env('HOMIS_ODBC_ENABLED', true), FILTER_VALIDATE_BOOL),
        'dsn' => env('HOMIS_ODBC_DSN', 'homis'),
        'username' => env('HOMIS_ODBC_USER', 'sa'),
        'password' => env('HOMIS_ODBC_PASSWORD', ''),
        'result_base_url' => env('HOMIS_RESULT_BASE_URL', 'https://weblis.qmmc.com/getPdf'),
        'host' => env('DB_HRBLIZDTR_HOST', '172.16.200.1'),
        'port' => env('DB_HRBLIZDTR_PORT', '1433'),
        'database' => env('DB_HRBLIZDTR_DATABASE', 'hospital'),
    ],

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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
