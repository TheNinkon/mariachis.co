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

    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_APP_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'trustpilot' => [
        'profile_url' => env('TRUSTPILOT_PROFILE_URL', 'https://www.trustpilot.com/review/mariachis.co'),
        'business_unit_id' => env('TRUSTPILOT_BUSINESS_UNIT_ID', '69b28501e5b9211cdbf13bc2'),
        'display_name' => env('TRUSTPILOT_DISPLAY_NAME', 'Mariachis Colombia'),
        'review_count' => (int) env('TRUSTPILOT_REVIEW_COUNT', 0),
        'trust_score' => (float) env('TRUSTPILOT_TRUST_SCORE', 0),
        'cache_minutes' => (int) env('TRUSTPILOT_CACHE_MINUTES', 60),
    ],

];
