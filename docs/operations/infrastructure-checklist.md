# Infrastructure Checklist

- [ ] PHP 8.2+ with required extensions
- [ ] Composer 2 available on deploy host
- [ ] Node 18+ available for asset build (CI or host)
- [ ] MySQL 8+ (or approved managed DB)
- [ ] TLS certificate valid (≥30 days remaining)
- [ ] Document root → `public/`
- [ ] `storage/` and `bootstrap/cache/` writable by web user
- [ ] `php artisan storage:link` if public disk used
- [ ] Queue worker supervised and restarting on failure
- [ ] Scheduler cron every minute
- [ ] Log rotation configured (`LOG_STACK=daily` recommended)
- [ ] Firewall: only required ports public
- [ ] Secrets only in env/vault — not in repo
- [ ] Staging environment available for pre-prod validation