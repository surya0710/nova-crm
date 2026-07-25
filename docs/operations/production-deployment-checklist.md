# Production Deployment Checklist

**Release:** ________ **Operator:** ________ **Date:** ________

## Pre-deploy
- [ ] Change ticket / release notes approved
- [ ] Backup DB + `storage/app` verified (timestamp recorded)
- [ ] `php artisan migrate:status` reviewed
- [ ] Rollback plan documented
- [ ] Maintenance window communicated (if downtime)
- [ ] Queue depth healthy before start

## Deploy
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build`
- [ ] `php artisan down` (optional)
- [ ] `php artisan migrate --force`
- [ ] `php artisan config:cache && route:cache && view:cache`
- [ ] `php artisan queue:restart`
- [ ] `php artisan up`

## Post-deploy
- [ ] `GET /up` → 200
- [ ] Tenant login works
- [ ] Platform login works (if changed)
- [ ] Smoke: [../release/smoke.md](../release/smoke.md)
- [ ] Monitoring nominal (15–30 min watch)
- [ ] Support notified of release

**Never:** `migrate:fresh` / `db:wipe` / `migrate:reset` on production.