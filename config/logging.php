<?php

return [

    'default' => env('LOG_CHANNEL', 'single'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    'channels' => [
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'notify_service' => [
            'driver' => 'daily',
            'path' => storage_path('logs/notify_service/notify-service.log'),
            'level' => 'debug',
            'days' => 365,
        ],

        'elk' => [
            'driver' => 'daily',
            'path' => storage_path('logs/elk/elk.log'),
            'level' => 'debug',
            'days' => 365
        ],
        'telegram' => [
            'driver' => 'daily',
            'path' => storage_path('logs/telegram/telegram.log'),
            'level' => 'debug',
            'days' => 365
        ],
    ],

];
