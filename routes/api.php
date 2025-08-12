<?php

use Illuminate\Support\Facades\Route;
use MattYeend\HealthStatus\Http\Controllers\HealthController;

$config = config('healthstatus.http', []);
$endpoint = $config['endpoint'] ?? '/healthstatus';
$middleware = config('healthstatus.middleware', []);

Route::middleware($middleware)->group(function () use ($endpoint, $config) {
    Route::get($endpoint, [HealthController::class, 'show']);
    if ($config['api_enabled'] ?? true) {
        Route::get($endpoint.'/json', [HealthController::class, 'json']);
    }
});
