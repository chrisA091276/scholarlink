<?php

return [
    'guard' => ['web'],
    'expiration' => (int) env('SANCTUM_EXPIRATION', 1440),
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:8080,127.0.0.1,127.0.0.1:8080')),
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
    'middleware' => [
        'authenticate_session' => \Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => \Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
