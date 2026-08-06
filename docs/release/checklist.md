# Release Checklist — Operators

Pre- and post-release checklist for Konnect Nex Release 1.2.x deployments. Pair with [production-readiness.md](./production-readiness.md), [smoke.md](./smoke.md), and the [queue/scheduler runbook](../deployment/queues-and-scheduler.md).

**Related:** [Deployment overview](../deployment/overview.md) · [UPGRADE.md](../../UPGRADE.md)

---

## Release metadata

| Field | Value |
|-------|-------|
| Phase / version | 1.2.x |
| Release date | |
| Environment | ☐ Staging ☐ Production |
| Deploy owner | |
| Rollback owner | |

---

## Pre-release (T-24h to T-0)

### Communication

- [ ] Release window communicated to stakeholders
- [ ] Breaking changes documented
- [ ] Support team briefed on [troubleshooting](../troubleshooting/overview.md) updates

### Code & build

- [ ] Target branch tagged or release artifact built
- [ ] `composer install --no-dev --optimize-autoloader` succeeds
- [ ] `npm ci && npm run build` succeeds
- [ ] `php artisan test --group=smoke` passes on staging
- [ ] Full test suite reviewed (known failures documented or fixed)

### Database

- [ ] `php artisan migrate:status` reviewed — forward-only migrations only
- [ ] **No** `migrate:fresh` / `migrate:refresh` / `db:wipe` planned
- [ ] Database backup verified (restore tested within last 30 days)
- [ ] `storage/app` backup plan confirmed (uploads)

### Configuration

- [ ] Staging `.env` matches production shape (keys present)
- [ ] Production `.env` secrets rotated if compromised
- [ ] `APP_DEBUG=false` · `APP_ENV=production`
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS)
- [ ] Mail, queue, cache, filesystem drivers set
- [ ] `ENTERPRISE_SHELL=true` (or rollback plan documented)
- [ ] Marketing OAuth credentials valid (if using Meta/Google)

### Infrastructure

- [ ] SSL certificate valid
- [ ] Multiple-worker Supervisor config ready, or bounded overlap-protected shared-host queue cron installed
- [ ] Separate overlap-protected `schedule:run` cron uses absolute paths and dedicated logging
- [ ] Disk space adequate for logs and uploads
- [ ] PHP 8.2+ and required extensions on target server

---

## Deploy (T-0)

- [ ] Maintenance mode (optional): `php artisan down --secret="{token}"`
- [ ] Deploy artifact / `git pull`
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build`
- [ ] `php artisan migrate --force`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan queue:restart`
- [ ] Supervisor workers return to `RUNNING` (or bounded cron workers exit normally)
- [ ] Reload PHP-FPM / web server if required
- [ ] `php artisan up` (if maintenance was enabled)

---

## Post-release (T+0 to T+1h)

### Immediate (first 15 minutes)

- [ ] `GET /up` → 200
- [ ] Tenant login succeeds
- [ ] Platform login succeeds (separate session)
- [ ] One workspace home per [smoke.md](./smoke.md) loads
- [ ] No 500 errors in `laravel.log`
- [ ] `php artisan queue:failed` — review new failures
- [ ] Benign queue canary writes a recent `queue.canary.last_run` timestamp

### Smoke (within 1 hour)

- [ ] Automated: `php artisan test --group=smoke` (on staging mirror or CI artifact)
- [ ] Manual critical paths from [smoke.md](./smoke.md) completed
- [ ] CRM lead import spot check (if imports are production-critical)
- [ ] Platform → Monitoring dashboard reviewed

### Monitoring (first 24 hours)

- [ ] Queue depth stable (Platform Monitoring or `jobs` table count)
- [ ] Failed job count not increasing
- [ ] Scheduler heartbeat key `platform.scheduler.last_run` stays fresh via OS `schedule:run`
- [ ] Error rate in logs normal
- [ ] Mail delivery confirmed (test or payslip job)

---

## Post-release (T+24h)

- [ ] No P1/P2 defects open from release
- [ ] Release notes updated ([release-notes/overview.md](../release-notes/overview.md))
- [ ] [P14_PHASE_14_9_PROGRESS.md](../P14_PHASE_14_9_PROGRESS.md) reflects deploy date if needed
- [ ] Retrospective notes captured (optional)

---

## Rollback triggers

Execute rollback if any of:

- `/up` unhealthy after deploy
- Widespread 500 on login or workspace homes
- Migration failure mid-deploy
- Data corruption detected in import or payroll
- Security regression (cross-tenant data visible)

### Rollback procedure

1. `php artisan down` (optional)
2. Restore previous release artifact
3. Restore database backup **if** migration altered data irreversibly
4. Or UI-only rollback: `ENTERPRISE_SHELL=false` + `php artisan config:clear`
5. `php artisan queue:restart`
6. Verify `/up` and tenant login
7. Document incident

Details: [UPGRADE.md](../../UPGRADE.md#rollback).

---

## Sign-off

| Checkpoint | Owner | Date/time | Pass |
|------------|-------|-----------|------|
| Pre-release complete | | | ☐ |
| Deploy complete | | | ☐ |
| Post-release smoke | | | ☐ |
| 24h monitoring | | | ☐ |

**Notes:**

---
