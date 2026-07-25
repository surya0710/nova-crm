# Upgrade Guide

Forward-compatible upgrades for NovaCRM. Prefer additive migrations; **do not** run `migrate:fresh`, `migrate:refresh`, or `db:wipe` against shared environments.

---

## Versioning

- Application releases follow dated Phase tags (e.g. Phase 14.9) plus semantic app version when published (`APP` / git tag).
- Database schema changes ship as numbered Laravel migrations under `database/migrations`.
- Feature flags in `config/features.php` allow UI rollback without schema rollback.

---

## Standard upgrade procedure

1. **Backup** database and `storage/` (especially uploaded files and `.env`).
2. Put the app in maintenance mode if needed: `php artisan down`.
3. Deploy code (git pull / artifact).
4. Install dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```
5. Run **forward** migrations only:
   ```bash
   php artisan migrate --force
   ```
6. Refresh caches:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan queue:restart
   ```
7. Smoke test (see [docs/release/smoke.md](docs/release/smoke.md)).
8. `php artisan up`.

---

## Feature-flag rollback (UI)

If Enterprise shell regressions appear after a frontend release:

```env
ENTERPRISE_SHELL=false
```

Then `php artisan config:clear` (or rebuild config cache). This restores legacy chrome while keeping tokenized components available. Prefer fixing the shell and re-enabling.

---

## Schema rollback

- Prefer **forward fixes** (new migrations) over rolling back.
- Only `php artisan migrate:rollback` when the release explicitly documents a safe single-step rollback and backups are verified.
- Never roll back past data-preserving migrations in multi-tenant production without a restore plan.

---

## Queue / scheduler

After upgrade, confirm:

- Queue workers restarted (`queue:restart` or process supervisor reload).
- Cron still runs `schedule:run` every minute.
- Failed jobs reviewed: `php artisan queue:failed` and Platform → Monitoring.

---

## Verification

```bash
php artisan about
curl -fsS "$APP_URL/up"
php artisan test --group=smoke
```

Full checklist: [docs/release/production-readiness.md](docs/release/production-readiness.md).
