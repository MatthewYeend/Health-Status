<?php

return [

    'pings' => [
        [
            'name' => 'google',
            'host' => '8.8.8.8',
            'port' => 53,
            'timeout' => 2,
        ],
    ],

    'queues' => [
        [
            'name' => 'default',
            'warning_threshold' => 50,
            'critical_threshold' => 200,
        ],
    ],

    'cron' => [
        'cache_key' => 'healthstatus:cron:last_run',
        'warning_seconds' => 120,
        'critical_seconds' => 600,
    ],

    'disks' => [
        [
            'name' => 'root',
            'path' => '/',
            'warning_free_percent' => 15,
            'critical_free_percent' => 5,
        ],
    ],

    'database' => [
        'connection' => null,
        'warning_response_time_ms' => 200,
        'critical_response_time_ms' => 2000,
    ],

    'http' => [
        'endpoint' => '/healthstatus',
        'view' => true,
        'api_enabled' => true,
    ],

    'middleware' => [
        // e.g. 'auth.basic'
    ],
];
