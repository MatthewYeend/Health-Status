<?php

namespace MattYeend\HealthStatus\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CronHeartbeat extends Command
{
    protected $signature = 'healthstatus:cron-heartbeat';
    protected $description = 'Updates cron heartbeat timestamp for health check.';

    public function handle()
    {
        $key = config('healthstatus.cron.cache_key', 'healthstatus:cron:last_run');
        $now = now()->toIsoString();
        Cache::forever($key, $now);
        $this->info("Cron heartbeat set at {$now}");
        return 0;
    }
}
