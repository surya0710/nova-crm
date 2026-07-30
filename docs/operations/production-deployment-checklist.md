# Production Deployment Checklist

**Release:** ________ **Operator:** ________ **Date:** ________

## Pre-deploy
- [ ] Release is in the supported 1.2.x line and release notes are approved
- [ ] Change ticket / release notes approved
- [ ] Backup DB + `storage/app` verified (timestamp recorded)
- [ ] `php artisan migrate:status` reviewed
- [ ] Rollback plan documented
- [ ] Maintenance window communicated (if downtime)
- [ ] Queue depth healthy before start
- [ ] Supervisor processes healthy, or bounded queue cron and separate scheduler cron enabled

## Deploy
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build`
- [ ] `php artisan down` (optional)
- [ ] `php artisan migrate --force`
- [ ] `php artisan config:cache && route:cache && view:cache`
- [ ] `php artisan queue:restart`
- [ ] Supervisor workers returned to `RUNNING` (bounded cron workers may exit normally)
- [ ] `php artisan up`

## Post-deploy
- [ ] `GET /up` → 200
- [ ] Tenant login works
- [ ] Platform login works (if changed)
- [ ] Smoke: [../release/smoke.md](../release/smoke.md)
- [ ] Monitoring nominal (15–30 min watch)
- [ ] Queue canary processed; `php artisan queue:failed` has no new records
- [ ] Scheduler key `platform.scheduler.last_run` updated by OS `schedule:run` within two minutes
- [ ] Support notified of release

Runtime commands and templates: [Queues and Scheduler — Release 1.2.x](../deployment/queues-and-scheduler.md).

**Never:** `migrate:fresh` / `db:wipe` / `migrate:reset` on production.