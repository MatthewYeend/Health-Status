<?php

namespace MattYeend\HealthStatus;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthChecker
{
    public function __construct(protected array $config = []) {}

    public function all(): array
    {
        $results = [
            'database' => $this->checkDatabase(),
            'pings' => $this->checkPings(),
            'queues' => $this->checkQueues(),
            'cron' => $this->checkCron(),
            'disks' => $this->checkDisks(),
        ];

        $overall = 'ok';
        foreach ($results as $r) {
            if ($r['status'] === 'critical') { $overall = 'critical'; break; }
            if ($r['status'] === 'warning' && $overall !== 'critical') $overall = 'warning';
        }

        return [
            'status' => $overall,
            'checks' => $results,
            'timestamp' => now()->toIsoString(),
        ];
    }

    public function checkDatabase(): array
    {
        $connection = $this->config['database']['connection'] ?? null;
        $warningMs = $this->config['database']['warning_response_time_ms'] ?? 200;
        $criticalMs = $this->config['database']['critical_response_time_ms'] ?? 2000;

        try {
            $start = microtime(true);
            $conn = DB::connection($connection);
            $conn->selectOne('select 1 as ok');
            $elapsed = (microtime(true) - $start) * 1000;

            $status = 'ok';
            if ($elapsed > $criticalMs) $status = 'critical';
            elseif ($elapsed > $warningMs) $status = 'warning';

            return [
                'status' => $status,
                'response_time_ms' => round($elapsed, 2),
                'connection' => $conn->getName(),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function checkPings(): array
    {
        $pings = $this->config['pings'] ?? [];
        $results = [];

        foreach ($pings as $p) {
            $name = $p['name'] ?? ($p['host'] ?? 'unknown');
            $host = $p['host'] ?? null;
            $port = $p['port'] ?? 80;
            $timeout = $p['timeout'] ?? 2;

            try {
                $start = microtime(true);
                $conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
                $elapsed = (microtime(true) - $start) * 1000;
                if (is_resource($conn)) {
                    fclose($conn);
                    $status = 'ok';
                } else {
                    $status = 'critical';
                }
                $results[$name] = [
                    'status' => $status,
                    'host' => $host,
                    'port' => $port,
                    'response_time_ms' => round($elapsed, 2),
                    'error' => $status === 'ok' ? null : ($errstr ?: 'connection failed'),
                ];
            } catch (Throwable $e) {
                $results[$name] = [
                    'status' => 'critical',
                    'host' => $host,
                    'port' => $port,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $agg = 'ok';
        foreach ($results as $r) {
            if ($r['status'] === 'critical') { $agg = 'critical'; break; }
            if ($r['status'] === 'warning') $agg = 'warning';
        }

        return [
            'status' => $agg,
            'hosts' => $results,
        ];
    }

    public function checkQueues(): array
    {
        $queues = $this->config['queues'] ?? [];
        $out = [];
        $agg = 'ok';

        foreach ($queues as $q) {
            $name = $q['name'] ?? 'default';
            $warning = $q['warning_threshold'] ?? 50;
            $critical = $q['critical_threshold'] ?? 200;
            $size = null;

            try {
                $size = Queue::size($name);
            } catch (Throwable $e) {
                $size = null;
            }

            $status = 'ok';
            if (is_numeric($size)) {
                if ($size >= $critical) $status = 'critical';
                elseif ($size >= $warning) $status = 'warning';
            } else {
                $status = 'warning';
            }

            if ($status === 'critical') $agg = 'critical';
            elseif ($status === 'warning' && $agg !== 'critical') $agg = 'warning';

            $out[$name] = [
                'status' => $status,
                'size' => $size,
                'thresholds' => ['warning' => $warning, 'critical' => $critical],
            ];
        }

        return [
            'status' => $agg,
            'queues' => $out,
        ];
    }

    public function checkCron(): array
    {
        $key = $this->config['cron']['cache_key'] ?? 'healthstatus:cron:last_run';
        $warning = $this->config['cron']['warning_seconds'] ?? 120;
        $critical = $this->config['cron']['critical_seconds'] ?? 600;

        try {
            $last = Cache::get($key);
            if (!$last) {
                return [
                    'status' => 'critical',
                    'error' => 'no heartbeat found',
                ];
            }
            $lastTs = now()->parse($last);
            $age = now()->diffInSeconds($lastTs);

            $status = 'ok';
            if ($age > $critical) $status = 'critical';
            elseif ($age > $warning) $status = 'warning';

            return [
                'status' => $status,
                'last_run' => $lastTs->toIsoString(),
                'age_seconds' => $age,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function checkDisks(): array
    {
        $disks = $this->config['disks'] ?? [];
        $out = [];
        $agg = 'ok';

        foreach ($disks as $d) {
            $name = $d['name'] ?? ($d['path'] ?? 'unknown');
            $path = $d['path'] ?? '/';
            $warnPercent = $d['warning_free_percent'] ?? 15;
            $critPercent = $d['critical_free_percent'] ?? 5;

            try {
                $total = @disk_total_space($path);
                $free = @disk_free_space($path);
                if ($total && $free) {
                    $freePercent = ($free / $total) * 100;
                    $status = 'ok';
                    if ($freePercent <= $critPercent) $status = 'critical';
                    elseif ($freePercent <= $warnPercent) $status = 'warning';
                    $details = [
                        'total_bytes' => $total,
                        'free_bytes' => $free,
                        'free_percent' => round($freePercent, 2),
                    ];
                } else {
                    $status = 'warning';
                    $details = ['error' => 'Unable to determine disk space'];
                }
            } catch (Throwable $e) {
                $status = 'critical';
                $details = ['error' => $e->getMessage()];
            }

            if ($status === 'critical') $agg = 'critical';
            elseif ($status === 'warning' && $agg !== 'critical') $agg = 'warning';

            $out[$name] = array_merge(['status' => $status, 'path' => $path], $details);
        }

        return [
            'status' => $agg,
            'disks' => $out,
        ];
    }
}
