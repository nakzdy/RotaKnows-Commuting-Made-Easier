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

    'gnews' => [
        'api_key' => env('GNEWS_API_KEY'),
        'base_url' => env('GNEWS_API_URL'),
    ],

    // Add LocationIQ config here:
    'locationiq' => [
        'api_key' => env('LOCATIONIQ_API_KEY'),
        'base_url' => 'https://us1.locationiq.com/v1', 
    ],

    // Add OpenWeather config here:
    'openweather' => [
        'api_key' => env('OPENWEATHER_API_KEY'),
        'base_url' => env('OPENWEATHER_API_URL', 'https://api.openweathermap.org/data/2.5/weather'),
    ],

    //TomTom
    'tomtom' => [
        'key' => env('TOMTOM_API_KEY'),
        'base_url' => env('TOMTOM_BASE_URL', 'https://api.tomtom.com'),
    ],

    'foursquare' => [
        'api_key' => env('FSQ_API_KEY'),
        'base_url' => 'https://api.foursquare.com/v3/places',
    ],

];
