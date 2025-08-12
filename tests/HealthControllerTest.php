<?php

namespace MattYeend\HealthStatus\Tests;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

class HealthControllerTest extends TestCase
{
    public function test_it_returns_json_health_status()
    {
        $response = $this->get('/healthstatus/json');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'checks' => ['database', 'pings', 'queues', 'cron', 'disks'],
            'timestamp'
        ]);
    }
}
