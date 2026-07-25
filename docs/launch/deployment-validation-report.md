# Deliverable 8 — Deployment Validation Report

**Mode:** SOP validation + **local** command execution  
**Not in scope:** Claiming Nginx/Supervisor/SSL/Redis/production mail results from XAMPP

Production-grade infrastructure validation must be repeated on staging/production (Hostinger VPS, AWS, DigitalOcean, etc.) using [`../deployment/`](../deployment/) and [`../sops/deployment/`](../sops/deployment/).

## SOP crosswalk

| Topic | SOP / Doc | Local status |
|-------|-----------|--------------|
| Production deploy | SOP-DEP-002, `docs/deployment/guide.md` | Checklist reviewed |
| Environment | SOP-DEP-003 | Local `.env` only |
| Queue workers | SOP-DEP-004 | `queue:restart` / `queue:failed` exercised |
| Scheduler | SOP-DEP-005 | `schedule:list` exercised |
| Cache | SOP-DEP-006 | `optimize` / config|route|view cache |
| Storage | SOP-DEP-007 | `storage:link` if missing |
| SSL / domain | SOP-DEP-008 / 009 | **Deferred** to real host |
| Eng readiness | `docs/release/production-readiness.md` | Referenced |

## Local command matrix

Executed 2026-07-25 on local XAMPP (Windows). Exit codes recorded. Do **not** treat success here as production sign-off.

| Command | Purpose | Result |
|---------|---------|--------|
| `composer install --no-dev --optimize-autoloader` | Prod-like deps | **Skipped** on shared local DX (would remove `require-dev`); run on staging/prod release hosts |
| `php artisan migrate --force` | Forward migrations only | Pass (exit 0 — nothing pending) |
| `php artisan organization:upgrade --all` | Module/prefs backfill | Pass (exit 0; all 7 orgs; second pass idempotent) |
| `php artisan optimize` | Bootstrap caches | Pass (exit 0) |
| `php artisan config:cache` | Config cache | Pass (exit 0) |
| `php artisan route:cache` | Route cache | Pass (exit 0) |
| `php artisan view:cache` | View cache | Pass (exit 0) |
| `php artisan queue:restart` | Signal workers | Pass (exit 0) |
| `php artisan schedule:list` | Scheduler inventory | Pass (exit 0 — 3 scheduled tasks listed) |
| `php artisan storage:link` | Public storage | Pass (exit 0 — link already existed) |
| `php artisan optimize:clear` | Restore local DX after validation | Pass (exit 0) |
| `php artisan pilot:seed` | Five pilot orgs | Pass (A–E provisioned) |
| `php docs/launch/scripts/verify-pilot-licensing.php` | Module allow matrix | Pass (see execution-log) |

**Guardrails:**

- Never run `migrate:fresh` / `db:wipe` on shared data.
- Frontend assets must be built in CI/release artifact — production server must not require `npm run build` (see deployment guide).

## Production follow-up (required before GA infra sign-off)

- [ ] Deploy release artifact to staging
- [ ] TLS / HTTPS verified
- [ ] Supervisor/systemd queue workers
- [ ] Cron → `schedule:run`
- [ ] Redis (if configured) health
- [ ] Mail transport smoke
- [ ] Backup job + restore drill
- [ ] Monitoring /alerts

Until those complete, deployment gate is **Conditional — application validated locally; infrastructure pending**.
