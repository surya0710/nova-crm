# Queues and Scheduler — Release 1.2.x

This is the canonical runtime runbook for NovaCRM queue workers and the Laravel scheduler. Use absolute paths in production and keep queue workers separate from `schedule:run`.

## Development: database queue

The default `.env.example` uses the durable database queue:

```env
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=390
QUEUE_FAILED_DRIVER=database-uuids
```

Run forward migrations once, then keep a worker running in a separate terminal:

```bash
php artisan migrate
php artisan queue:work database --queue=default --sleep=1 --tries=3 --timeout=360
```

`DB_QUEUE_RETRY_AFTER` must remain greater than `--timeout` so a long job is not delivered twice. The Composer `dev` script starts a development queue listener automatically. Do not use `QUEUE_CONNECTION=sync` to validate asynchronous behavior.

## Linux/VPS: Supervisor

Copy [the Supervisor template](../../deploy/supervisor/nova-crm-worker.conf.example) to `/etc/supervisor/conf.d/nova-crm-worker.conf`, replace every placeholder, then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status nova-crm-worker:*
```

The template listens to the application's `default,imports,exports,bulk,provisioning,mail` queues, starts four worker processes, starts them at boot, restarts unexpected exits, and allows 390 seconds for graceful shutdown. Change `numprocs` based on measured traffic, but do not remove a queue unless its producers are disabled. Keep the worker `--timeout=360` below the backend `retry_after=390`.

## Shared hosting: cPanel/Plesk cron

Where Supervisor is unavailable, install both entries from [the cron template](../../deploy/cron/cpanel-plesk.cron.example). Replace PHP, project, lock, and log paths with absolute host paths.

The queue entry runs every minute but is deliberately bounded:

```cron
* * * * * /usr/bin/flock -n /home/ACCOUNT/tmp/nova-crm-queue.lock /usr/local/bin/php /home/ACCOUNT/nova-crm/artisan queue:work database --queue=default,imports,exports,bulk,provisioning,mail --stop-when-empty --max-time=50 --sleep=1 --tries=3 --timeout=360 >> /home/ACCOUNT/logs/nova-crm-queue.log 2>&1
```

`flock -n` prevents overlapping invocations. `--stop-when-empty` and `--max-time=50` bound idle runtime; a job already running is still allowed to finish. If the host has no `flock`, use the control panel's overlap prevention or ask the provider for an equivalent lock—do not install an unguarded every-minute worker.

Install the scheduler as a separate cron:

```cron
* * * * * /usr/bin/flock -n /home/ACCOUNT/tmp/nova-crm-schedule.lock /usr/local/bin/php /home/ACCOUNT/nova-crm/artisan schedule:run --no-interaction >> /home/ACCOUNT/logs/nova-crm-schedule.log 2>&1
```

Do not call `schedule:heartbeat` directly from OS cron. `schedule:run` invokes it every minute and writes cache key `platform.scheduler.last_run`.

## Deployment and graceful worker restart

After dependencies, forward migrations, and cache rebuilds:

```bash
php artisan queue:restart
```

The command signals long-lived workers to exit after their current job; Supervisor starts replacements. Confirm all configured processes return to `RUNNING`. Bounded shared-hosting workers exit naturally, but running `queue:restart` remains safe and ensures any active worker reloads code.

## Failed-job recovery

1. Stop or fix the failing dependency/producer before retrying.
2. Inspect failures: `php artisan queue:failed` and the Platform Monitoring failed-job panel.
3. Retry one verified id: `php artisan queue:retry {uuid-or-id}`.
4. Retry a reviewed set in small batches; use `queue:retry all` only after impact approval.
5. Permanently remove one unrecoverable record with `queue:forget {uuid-or-id}` only after retaining incident evidence.
6. Never run `queue:flush` as routine recovery; it permanently discards all failed-job records.
7. Confirm pending depth and oldest age fall, failures stop increasing, and affected business records completed exactly once.

## Health validation and canary

After deploy:

```bash
php artisan config:show queue.default
php artisan queue:failed
php artisan schedule:list
php artisan schedule:heartbeat
```

`schedule:heartbeat` is a manual functional test. Platform Monitoring must show a fresh scheduler timestamp backed by `platform.scheduler.last_run`; then wait two minutes and confirm OS cron continues updating it.

For a queue canary, enqueue a benign one-time cache write and wait for a worker:

```bash
php artisan tinker --execute="dispatch(function () { cache()->put('queue.canary.last_run', now()->toIso8601String(), 600); });"
php artisan tinker --execute="dump(cache()->get('queue.canary.last_run'));"
```

The second command must print a recent timestamp. Also verify:

- the canary leaves pending depth and failed count unchanged after completion;
- each Supervisor process is `RUNNING`, or shared-host cron logs show clean minute-by-minute exits;
- queue logs have no timeout, duplicate-processing, permission, or memory errors;
- `GET /up` returns 200 and Platform Monitoring reports queue/scheduler health;
- `storage/logs` and host cron logs are writable and rotated.

Treat a missing canary timestamp, scheduler age over `SCHEDULER_STALE_AFTER` (default 180 seconds), growing backlog, or any new failed job as a failed deployment check.
