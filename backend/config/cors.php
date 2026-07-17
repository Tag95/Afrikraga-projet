<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'sanctum/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173', 
        'http://localhost:3000', 
        'http://localhost:8080',
        'https://afrikraga.com',
        'https://www.afrikraga.com',
        'https://api.afrikraga.com'
    ],
    'allowed_origins_patterns' => [
        '/^http:\/\/192\.168\.11\.\d+:5173$/',
        '/^http:\/\/192\.168\.11\.\d+:3000$/',
        '/^http:\/\/192\.168\.11\.\d+:8080$/',
        '/^http:\/\/host\.docker\.internal:\d+$/'
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
    'allowed_credentials' => true,
];