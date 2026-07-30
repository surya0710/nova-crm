# Administrator Guide — Monitoring & Operations

Operational visibility for NovaCRM without Laravel Telescope or Horizon. Use the Platform Monitoring UI, health endpoint, queue CLI, application logs, and scheduler heartbeat.

**Related:** [Deployment overview](../deployment/overview.md) · [Production readiness](../release/production-readiness.md) · [Troubleshooting](../troubleshooting/overview.md)

---

## Monitoring entry points

| Signal | Where | Permission |
|--------|-------|------------|
| Platform Monitoring UI | `/platform/monitoring` · route `platform.monitoring.index` | `platform.monitoring.view` |
| Health check | `GET /up` | Public (no auth) |
| Failed jobs CLI | `php artisan queue:failed` | Server shell |
| Application logs | `storage/logs/laravel.log` | Server shell / log aggregator |
| Scheduler heartbeat | Cache key `platform.scheduler.last_run` | Set by scheduled `schedule:heartbeat` command |

Open Monitoring from Platform dashboard widgets (**Queue Health**, **Background Jobs**) or command palette → “Open Monitoring”.

---

## Platform Monitoring UI

**Route:** `platform.monitoring.index`  
**Service:** `PlatformMonitoringService::snapshot()`

### Panels

| Panel | What it shows |
|-------|---------------|
| Queue | Connection/queue, pending and failed counts, recent success/failure/duration metrics, active/stale workers, status (`healthy` / `busy` / `degraded`) |
| Failed jobs | Last 10 failures with exception snippet and timestamp |
| Scheduler | Last recorded scheduler activity |
| Cache | Driver and read/write ping |
| Redis | Ping when redis is cache or queue driver |
| Database | Connectivity and latency (ms) |
| Storage | Aggregate org storage usage |
| Logs | Tail of last ~30 lines from `laravel.log` |
| System | PHP/Laravel versions, env, composite health |

### Status interpretation

| Status | Meaning | Action |
|--------|---------|--------|
| `healthy` | Queue failed = 0, pending reasonable | None |
| `busy` | Pending > 100 | Scale workers; investigate slow jobs |
| `degraded` | Failed jobs > 0 | `queue:failed` → fix → retry |
| `unhealthy` | Cache/DB ping failed | Check infrastructure |

Refresh the page after remediation; snapshot is point-in-time.

---

## Queue management (CLI)

Run on the application server (or worker host):

```bash
# List failed jobs
php artisan queue:failed

# Retry one job
php artisan queue:retry {uuid-or-id}

# Retry all failed (use with caution)
php artisan queue:retry all

# Forget a failed job without retry
php artisan queue:forget {uuid-or-id}

# Restart workers after deploy
php artisan queue:restart

# Run worker (production: use supervisor instead)
php artisan queue:work --sleep=1 --tries=3 --timeout=360
```

**Do not** `queue:flush` in production unless you accept permanent job loss.

Workflow and payroll jobs may exceed default timeout — `--timeout=360` is recommended.
Use [Queues and Scheduler — Release 1.2.x](../deployment/queues-and-scheduler.md) for Supervisor/shared-hosting templates, graceful restart, failed-job recovery, and a benign queue canary.

---

## Application logs

### Configuration (production)

```env
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
```

Files rotate under `storage/logs/`. Platform Monitoring shows a short tail; use SSH or a log shipper (Datadog, CloudWatch, etc.) for search and retention.

### What to grep

```bash
grep -i "error\|exception\|failed" storage/logs/laravel.log | tail -20
```

Correlate with failed job UUID and timestamp from Monitoring UI.

---

## Health endpoint

```bash
curl -fsS "$APP_URL/up"
```

Laravel built-in health route. Use for:

- Load balancer health checks
- Uptime monitors (Pingdom, UptimeRobot, etc.)
- Post-deploy smoke ([smoke.md](../release/smoke.md))

Expect HTTP 200 when application boots and dependencies are reachable.

---

## Scheduler heartbeat

Cron (every minute):

```cron
* * * * * /usr/bin/flock -n /home/ACCOUNT/tmp/nova-crm-schedule.lock /usr/local/bin/php /home/ACCOUNT/nova-crm/artisan schedule:run --no-interaction >> /home/ACCOUNT/logs/nova-crm-schedule.log 2>&1
```

Registered schedule (`routes/console.php`):

| Command | Frequency | Purpose |
|---------|-----------|---------|
| `schedule:heartbeat` | Every minute | Writes `platform.scheduler.last_run` to cache |
| `recruitment:process-integration-retries` | Every 5 minutes | Recruitment integration retries |
| `projects:generate-recurring-tasks` | Hourly | Recurring project tasks |

Manual heartbeat test:

```bash
php artisan schedule:heartbeat
```

The manual command tests the cache write only. If scheduler appears “unknown” or the heartbeat is older than `SCHEDULER_STALE_AFTER` (default 180 seconds), inspect the separate `schedule:run` cron and its log.

---

## Audit logs

Tenant and platform actions write to audit tables / in-app audit views:

- Platform: Security / Audit sections under `/platform`
- Tenant: Module-specific audit (CRM, HRMS, admin) where enabled

Audit complements application logs for compliance and security investigations — not a substitute for queue/log monitoring.

---

## What is not included

| Tool | Status | Alternative |
|------|--------|-------------|
| Laravel Telescope | Not bundled | Platform Monitoring + logs |
| Laravel Horizon | Not bundled | `queue:failed`, `jobs` table, Monitoring UI |
| APM (New Relic, etc.) | Optional external | Install at infrastructure layer if needed |

---

## Recommended production setup

1. **Supervisor** — multiple worker processes covering every configured queue; use a graceful TERM stop window longer than job timeout.
2. **Shared-host queue cron** — only when Supervisor is unavailable; bounded and protected against overlap.
3. **Scheduler cron** — separate overlap-protected `schedule:run` every minute on the app server.
4. **Uptime** — external monitor on `/up`.
5. **Log retention** — daily rotation, 14–30 day retention minimum.
6. **Alerts** — notify on `/up` failure, failed job count threshold, disk > 85%.
7. **Post-deploy** — restart workers, run the queue canary, and check Monitoring within 15 minutes ([checklist.md](../release/checklist.md)).

---

## Related commands

```bash
php artisan about
php artisan migrate:status
php artisan config:show queue.default
php artisan config:show cache.default
php artisan test --group=smoke
```

Upgrade and restart workers after every release: [UPGRADE.md](../../UPGRADE.md).
