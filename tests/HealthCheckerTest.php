<?php

namespace MattYeend\HealthStatus\Tests;

use PHPUnit\Framework\TestCase;
use MattYeend\HealthStatus\HealthChecker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class HealthCheckerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // We assume Laravel is already bootstrapped by the host app running phpunit
    }

    public function test_it_checks_database_connection()
    {
        DB::shouldReceive('connection->selectOne')
            ->once()
            ->andReturn((object)['ok' => 1]);

        $checker = new HealthChecker(config('healthstatus'));
        $result = $checker->checkDatabase();

        $this->assertArrayHasKey('status', $result);
    }

    public function test_it_checks_cron_heartbeat()
    {
        Cache::shouldReceive('get')->andReturn(now()->toIsoString());

        $checker = new HealthChecker(config('healthstatus'));
        $result = $checker->checkCron();

        $this->assertEquals('ok', $result['status']);
    }

    public function test_it_checks_queues()
    {
        Queue::shouldReceive('size')->andReturn(0);

        $checker = new HealthChecker(config('healthstatus'));
        $result = $checker->checkQueues();

        $this->assertEquals('ok', $result['status']);
    }
}
