# NovaCRM

Multi-tenant SaaS CRM + Projects (EPM) + HRMS/Recruitment + Marketing attribution + Analytics, with a SaaS owner console at `/platform`.

**Stack:** Laravel · Blade + Alpine · Vite · Tailwind · MySQL/SQLite · Sanctum API

---

## Requirements

- PHP 8.2+
- Composer 2
- Node.js 18+ (Vite assets)
- MySQL 8+ (recommended) or SQLite for local
- A queue worker and OS scheduler for production

---

## Quick start (local)

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB_* in .env, then:
php artisan migrate
npm install && npm run build
php artisan serve
```

Optional XAMPP: set `APP_URL` / `ASSET_URL` to your `/nova-crm/public` path (see `.env.example`).

---

## Production essentials

| Concern | Command / note |
|---------|----------------|
| Assets | `npm ci && npm run build` |
| Optimize | `php artisan config:cache && php artisan route:cache && php artisan view:cache` |
| Migrate | `php artisan migrate --force` (forward-only; never `migrate:fresh` in prod) |
| Queue | `php artisan queue:work --sleep=1 --tries=3` (or supervisor) |
| Scheduler | Cron: `* * * * * php /path/to/artisan schedule:run` |
| Health | `GET /up` |
| Monitoring | Platform console → Monitoring (`platform.monitoring.index`) |

See [docs/deployment/overview.md](docs/deployment/overview.md) and [docs/release/production-readiness.md](docs/release/production-readiness.md).

---

## Workspaces

| Workspace | Entry |
|-----------|-------|
| Home / My Work | `/` |
| CRM | `/crm` |
| Projects | `/projects` |
| HR | `/hrms` |
| Marketing | `/marketing` |
| Analytics | `/analytics` |
| Administration | `/administration` |
| Platform (SaaS owner) | `/platform` |

Enterprise shell flags: `config/features.php` (`ENTERPRISE_SHELL`, etc.). Rollback UI chrome: `ENTERPRISE_SHELL=false`.

---

## Documentation

| Guide | Path |
|-------|------|
| Getting started | [docs/getting-started/overview.md](docs/getting-started/overview.md) |
| Deployment | [docs/deployment/overview.md](docs/deployment/overview.md) |
| Upgrade | [UPGRADE.md](UPGRADE.md) |
| Production readiness | [docs/release/production-readiness.md](docs/release/production-readiness.md) |
| Launch readiness (Program 15) | [docs/launch/README.md](docs/launch/README.md) |
| Troubleshooting | [docs/troubleshooting/overview.md](docs/troubleshooting/overview.md) |
| API overview | [docs/api/overview.md](docs/api/overview.md) |
| Frontend migration | [docs/frontend/migration-progress.md](docs/frontend/migration-progress.md) |
| Knowledge Center | In-app `/knowledge` (when enabled) |
| Demo environment | `php artisan demo:seed-presentation` · [docs/demos/](docs/demos/) |

Module user/admin guides live under `docs/crm`, `docs/hrms`, `docs/projects`, `docs/admin-guide`, and related trees. Commercial SOPs, sales assets, and pilot playbooks live under `docs/sops`, `docs/sales`, `docs/operations`, `docs/launch`, and related Program 15 folders (repo / internal; not exposed in customer Knowledge Center).

---

## Testing

```bash
php artisan test
php artisan test --group=smoke
```

---

## License

Proprietary — NovaCRM. Laravel framework components retain their upstream licenses.
