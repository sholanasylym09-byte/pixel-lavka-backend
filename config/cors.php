<?php

/*
| CORS: клиент (frontend) работает на другом порту, поэтому браузеру
| нужно явно разрешить обращение к API. Разрешены только адреса из FRONTEND_URL.
*/
return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env('FRONTEND_URL', 'http://localhost:5500')))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 3600,

    // Авторизация по Bearer-токену — cookie не используются
    'supports_credentials' => false,
];
