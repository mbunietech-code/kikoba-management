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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'beem' => [
        'key' => env('BEEM_API_KEY'),
        'secret' => env('BEEM_SECRET_KEY'),
        'sender_id' => env('BEEM_SENDER_ID', 'INFO'),
        'sms_url' => env('BEEM_SMS_URL', 'https://apisms.beem.africa/v1/send'),
        'enabled' => env('SMS_ENABLED', false),
    ],

    'auth' => [
        'access_ttl' => (int) env('ACCESS_TOKEN_TTL', 900),
        'refresh_ttl' => (int) env('REFRESH_TOKEN_TTL', 1209600),
        'otp_ttl' => (int) env('OTP_TTL', 300),
        'otp_length' => (int) env('OTP_LENGTH', 6),
    ],

];
