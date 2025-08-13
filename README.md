# Laravel HealthStatus

A Laravel package to monitor application health, including:

- Database connection & latency
- Ping status for external hosts
- Queue sizes
- Cron heartbeat
- Disk space

Supports **Laravel 10, 11, and 12**.

---

## Installation

Require the package via Composer:

```bash
composer require MattYeend/health-status
```

---

## Publish Config & Views
```bash
php artisan vendor:publish --provider="MattYeend\HealthStatus\HealthStatusServiceProvider" --tag=config
php artisan vendor:publish --provider="MattYeend\HealthStatus\HealthStatusServiceProvider" --tag=views
```

--- 

## Configuration
The published file `config/healthstatus.php` contains:
- Hosts to ping
- Queues to check
- Cron heartbeat settings
- Disk space thresholds
- Database response time thresholds
- HTTP endpoint settings
- Middleware for endpoint protection
Example:
```php
'pings' => [
    [
        'name' => 'google',
        'host' => '8.8.8.8',
        'port' => 53,
        'timeout' => 2,
    ],
],
```

--- 

## Health Endpoint
By default:
- Web view: `/healthstatus`
- JSON: `/healthstatus/json`
You can change `http.endpoint` in `config/healthstatus.php.`

--- 

## Cron Heartbeat
### In Laravel 10, 11
Add to `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('healthstatus:cron-heartbeat')->everyMinute();
}
```
### In Laravel 12
Add to `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('healthstatus:cron-heartbeat')->everyMinute();
```
----
Then make sure your server runs Laravel’s scheduler:
```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```
---

## Example Output
JSON
```json
{
  "status": "ok",
  "checks": {
    "database": { "status": "ok", "response_time_ms": 15.3, "connection": "mysql" },
    "pings": { "status": "ok", "hosts": { "google": { "status": "ok" } } },
    "queues": { "status": "ok", "queues": { "default": { "status": "ok", "size": 0 } } },
    "cron": { "status": "ok", "last_run": "2025-08-12T15:20:00Z", "age_seconds": 10 },
    "disks": { "status": "ok", "disks": { "root": { "status": "ok", "free_percent": 75.1 } } }
  },
  "timestamp": "2025-08-12T15:20:10Z"
}
```

---

## Securing the Endpoint
Add middleware in `config/healthstatus.php`:
```php
'middleware' => ['auth.basic'],
```

--- 

## Testing
Run the included tests:
```bash
vendor/bin/phpunit
```

---

## License
This package is licensed under the MIT License.

---

## Contributing
Feel free to fork the repository and submit pull requests for improvements or new features!