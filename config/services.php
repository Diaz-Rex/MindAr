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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'zoho' => [
        'client_id' => env('ZOHO_CLIENT_ID'),
        'client_secret' => env('ZOHO_CLIENT_SECRET'),
        'redirect_uri' => env('ZOHO_REDIRECT_URI'),
        'accounts_url' => env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com'),
        'api_base' => env('ZOHO_API_BASE', 'https://www.zohoapis.com'),
        'workdrive_api_base' => env('ZOHO_WORKDRIVE_API_BASE', 'https://www.zohoapis.com/workdrive/api/v1'),
        'accounts_urls' => [
            'US' => 'https://accounts.zoho.com',
            'EU' => 'https://accounts.zoho.eu',
            'IN' => 'https://accounts.zoho.in',
            'AU' => 'https://accounts.zoho.com.au',
            'JP' => 'https://accounts.zoho.jp',
            'CA' => 'https://accounts.zohocloud.ca',
            'SA' => 'https://accounts.zoho.sa',
            'UK' => 'https://accounts.zoho.uk',
        ],
    ],

];
