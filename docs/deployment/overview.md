# Deployment Overview

Production deployment runbook for NovaCRM (tenant app + `/platform` console).

---

## Architecture notes

- **Tenant app:** `web` guard, `set.organization` / `ensure.organization`.
- **Platform console:** separate `platform` guard and session cookie — never mix with tenant sessions.
- **Health:** Laravel `GET /up`.
- **Observability:** Platform Monitoring UI (`platform.monitoring.index`) — queue depth, failed jobs, cache, DB, storage. Telescope/Horizon are **not** bundled; use platform monitoring + application logs.

---

## Pre-deployment checklist

- [ ] Backup database and `storage/app`
- [ ] Confirm `.env` for target environment (see Production configuration below)
- [ ] Review pending migrations (`php artisan migrate:status`)
- [ ] Confirm rollback / upgrade notes in [UPGRADE.md](../../UPGRADE.md)
- [ ] Communicate release window

---

## Deploy steps

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Optional maintenance window:

```bash
php artisan down
# … deploy …
php artisan up
```

---

## Runtime processes

| Process | Requirement |
|---------|-------------|
| PHP-FPM / Apache | Serve `public/` |
| Queue worker | Supervisor: four workers on `default,imports,exports,bulk,provisioning,mail`; shared hosting: locked, bounded direct worker cron |
| Scheduler | Separate, overlap-protected `schedule:run` cron every minute |
| Vite assets | Built at deploy time (`npm run build`); do not rely on `npm run dev` in prod |

Canonical setup, templates, recovery, and canary checks: [Queues and Scheduler — Release 1.2.x](queues-and-scheduler.md).

Scheduled jobs (current):

- `recruitment:process-integration-retries` — every 5 minutes
- `projects:generate-recurring-tasks` — hourly
- `schedule:heartbeat` — every minute (scheduler liveness for monitoring)

---

## Production configuration

| Variable | Recommendation |
|----------|----------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | Canonical HTTPS URL |
| `LOG_STACK` | `daily` |
| `LOG_LEVEL` | `warning` or `error` |
| `SESSION_SECURE_COOKIE` | `true` (HTTPS) |
| `SESSION_SAME_SITE` | `lax` (or `strict` if compatible) |
| `SANCTUM_STATEFUL_DOMAINS` | Your SPA/API hostnames |
| `QUEUE_CONNECTION` | `database` or `redis` |
| `DB_QUEUE_RETRY_AFTER` / `REDIS_QUEUE_RETRY_AFTER` | `390` (greater than the 360-second worker timeout) |
| `QUEUE_FAILED_DRIVER` | `database-uuids` |
| `SCHEDULER_STALE_AFTER` | `180` seconds |
| `CACHE_STORE` | `redis` or `database` |
| `ENTERPRISE_SHELL` | `true` unless rolling back UI |

Also configure mail, filesystem/S3, marketing OAuth platform credentials, and SSL termination at the reverse proxy.

---

## Post-deployment

- [ ] `GET /up` returns 200
- [ ] Login tenant + platform (isolation)
- [ ] Run [smoke checklist](../release/smoke.md)
- [ ] Check Platform → Monitoring / `queue:failed`
- [ ] Run the queue canary and confirm scheduler cache key `platform.scheduler.last_run` remains fresh
- [ ] Confirm mail delivery (test notification or `organization.test-mail` where permitted)

---

## Rollback

1. Restore previous release artifact.
2. Restore DB backup if a migration cannot be safely reversed.
3. Or set `ENTERPRISE_SHELL=false` for UI-only rollback.
4. `php artisan queue:restart` and clear/rebuild caches as needed.

Details: [UPGRADE.md](../../UPGRADE.md).
