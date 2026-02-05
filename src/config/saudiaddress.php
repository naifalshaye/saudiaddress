<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Saudi National Address API URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Saudi National Address API.
    |
    */

    'url' => env('SAUDI_ADDRESS_API_URL', 'https://apina.address.gov.sa/NationalAddress/v3.1'),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your API key obtained from https://api.address.gov.sa/
    |
    */

    'api_key' => env('SAUDI_ADDRESS_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Response Format
    |--------------------------------------------------------------------------
    |
    | The response format for API requests. Supported: JSON
    |
    */

    'format' => 'JSON',

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | The default language for API responses.
    | 'A' for Arabic, 'E' for English.
    |
    */

    'language' => env('SAUDI_ADDRESS_LANGUAGE', 'A'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for HTTP requests to the API.
    |
    */

    'timeout' => env('SAUDI_ADDRESS_TIMEOUT', 30),

];
