# SOP — Technical Operations

> **Superseded for execution by Phase 15.1.1 numbered SOPs.**  
> Use [INDEX.md](INDEX.md) → Deployment, Maintenance, Monitoring, Release Management, and Disaster Recovery. This family document is retained for deep-link compatibility.

---
**Document control**
| Field | Value |
|-------|-------|
| Version | 1.1 |
| Owner | Operations |
| Review cadence | Quarterly |
| Last reviewed | 2026-07-25 |
| Status | Legacy reference (see INDEX) |

## Purpose
Operate Konnect Nex platforms safely: deploy, configure, migrate, monitor, back up, upgrade, and roll back.

## Roles
| Role | Responsibility |
|------|----------------|
| DevOps / Platform Engineer | Deploy, infra, SSL, storage |
| Backend Lead | Migrations, queue, scheduler |
| On-call | Incidents outside business hours |

Canonical runbooks: [Deployment overview](../deployment/overview.md) · [Operations](../operations/README.md)

## 1. Deployment
Follow [Production Deployment Checklist](../operations/production-deployment-checklist.md).

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

**Never** run `migrate:fresh`, `migrate:refresh`, `db:wipe`, or `migrate:reset` on shared/production databases.

## 2. Environment configuration
Verify production `.env` against [deployment overview](../deployment/overview.md) (`APP_ENV`, `APP_DEBUG=false`, HTTPS cookies, queue/cache stores).

## 3. Database migration
1. `php artisan migrate:status` — review pending
2. Backup DB before migrate
3. `php artisan migrate --force`
4. Smoke: `GET /up` and critical login paths

## 4. Cache management
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
# After config change:
php artisan config:clear && php artisan config:cache
```

## 5. Queue workers
- Development: database queue plus `php artisan queue:work database --queue=default --sleep=1 --tries=3 --timeout=360`
- Production: use the [Supervisor or bounded shared-hosting templates](../deployment/queues-and-scheduler.md) with all configured queues covered
- After deploy: `php artisan queue:restart`, confirm replacement workers, then run the benign queue canary
- Monitor depth, oldest age, failed jobs, and worker logs via Platform → Monitoring

## 6. Scheduler
Install `schedule:run` as a separate every-minute cron with absolute paths, overlap protection, and dedicated logging. Confirm cache key `platform.scheduler.last_run` and scheduled jobs appear healthy in monitoring.

## 7. SSL
Terminate TLS at reverse proxy; `APP_URL` must be `https://…`; `SESSION_SECURE_COOKIE=true`.

## 8. Storage
Writable `storage/` and `bootstrap/cache/`; `php artisan storage:link` when needed; backup `storage/app` with DB.

## 9. Monitoring
Platform → Monitoring (`platform.monitoring.index`). Health: `GET /up`.

## 10. Backup
See [Backup Verification](../operations/backup-verification.md).

## 11. Disaster recovery
See [Incident Response Plan](../operations/incident-response-plan.md).

## 12. Upgrade process
1. Read [UPGRADE.md](../../UPGRADE.md) and release notes
2. Backup → deploy code + migrate → warm caches → restart queues
3. Smoke tests ([smoke.md](../release/smoke.md))

## 13. Rollback process
1. `php artisan down` if needed
2. Redeploy previous release artifact
3. Restore DB only if migration irreversible and approved
4. `php artisan up`
5. RCA within 48 hours

## Exit criteria
Deploy logged, health green, smoke passed, monitoring nominal.