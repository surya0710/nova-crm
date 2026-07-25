# Production Readiness — Phase 14.9

Master checklist for NovaCRM commercial deployment. Phase 14.9 adds **no major business modules**; it finalizes enterprise UX consistency, performance, security, observability, documentation, and QA across all workspaces built in Phases 1–14.

**Related:** [README.md](../../README.md) · [UPGRADE.md](../../UPGRADE.md) · [Deployment overview](../deployment/overview.md) · [Smoke tests](./smoke.md) · [Release checklist](./checklist.md) · [Troubleshooting](../troubleshooting/overview.md)

---

## Release gate

All sections below must pass before marking production-ready. Use [checklist.md](./checklist.md) for operator sign-off.

| Gate | Command / action |
|------|------------------|
| Migrations current | `php artisan migrate:status` — no pending |
| Assets built | `npm ci && npm run build` |
| Caches warm | `php artisan config:cache && php artisan route:cache && php artisan view:cache` |
| Automated smoke | `php artisan test --group=smoke` |
| Health | `GET /up` → 200 |
| Manual smoke | [smoke.md](./smoke.md) |

---

## 1. Enterprise UX consistency audit

**Standard:** `x-layouts.*` / `x-ui.*` shells, `x-nav.breadcrumbs`, `x-ui.empty-state-preset`, flash messages via `x-flash-messages` / session status keys, command palette (`⌘K` / `Ctrl+K`), global search modal.

| Workspace | Home route | Audit status | Notes |
|-----------|------------|--------------|-------|
| CRM | `crm.home` | ✅ Pass | Entity listing/detail/form layouts; Leads reference; export hub |
| Projects | `projects.home` | ✅ Pass | EPM home, portfolios/programs/risks/resources chrome |
| HRMS | `hrms.home` | ✅ Pass | People/Time/Leave/Recruitment/Payroll nav; ESS mode |
| Platform Admin | `platform.dashboard` | ✅ Pass | Isolated `/platform` guard; monitoring widgets |
| Org Admin | `administration.home` | ✅ Pass | Modules/security/branding/developer hubs |
| Marketing | `marketing.home` | ✅ Pass | Campaigns, attribution, providers |
| Analytics | `analytics.home` | ✅ Pass | Executive/CRM/Projects/HR, KPI library, AI insights review banner |

### Per-workspace verification checklist

- [ ] Workspace home loads with widget grid and quick actions
- [ ] Sidebar labels match [product glossary](../product/product-glossary.md)
- [ ] Breadcrumbs present on entity detail and nested settings pages
- [ ] Empty lists use `x-ui.empty-state-preset` (not raw “No data” text)
- [ ] Form submit shows flash success or inline validation errors
- [ ] Loading: skeleton or `x-ui.loading` on slow aggregates (not blank flash)
- [ ] Command palette lists workspace-scoped destinations
- [ ] Global search returns categorized results for that workspace’s entities
- [ ] Dialog/drawer patterns use `x-ui.modal` / `x-ui.drawer` (not ad-hoc overlays)
- [ ] Typography, spacing, and icon sizing match shared Tailwind tokens ([tailwind-standards.md](../frontend/tailwind-standards.md))

**Rollback:** Set `ENTERPRISE_SHELL=false` in `.env` and rebuild config cache. See [UPGRADE.md](../../UPGRADE.md).

---

## 2. Component library status

Canonical namespaces (Phase 14.1+):

| Namespace | Purpose |
|-----------|---------|
| `x-ui.*` | Buttons, cards, badges, empty states, modals, drawers |
| `x-forms.*` | Inputs, fields, sections, footers |
| `x-layouts.*` | Workspace home, entity listing/detail/form, settings, analytics |
| `x-nav.*` | Sidebar, breadcrumbs, workspace switcher, command palette, search |
| `x-workspace.*` | Widget grid, stat cards, quick actions |
| `x-entity.*` / `x-activity.*` | Detail sections, timelines |
| `x-tables.*` | Table shell, toolbar, pagination |
| `x-shell.*` | Header, context bar, notification drawer |

**Wave 8 (ongoing):** Legacy `x-primary-button`, `x-secondary-button`, `x-danger-button`, `x-text-input`, `x-sidebar-link` remain as **thin aliases** forwarding to canonical components. Do not add new usages of legacy tags in workspace code; prefer canonical tags on touched files.

**Phase 14.9:** Auth screens (`resources/views/auth/*`, `resources/views/platform/auth/*`) and guest layouts use aliased components (resolving to `x-ui.button` / `x-forms.input`) plus Vite-built assets. Full tag purge deferred until `rg` shows zero legacy references.

Catalog: [component-catalog.md](../frontend/component-catalog.md) · Progress: [migration-progress.md](../frontend/migration-progress.md).

---

## 3. Performance

| Area | Implementation | Check |
|------|----------------|-------|
| Workspace home aggregates | `CachesWorkspaceHome` trait + `DashboardCache` (org-scoped version bump, default TTL 300s via `DASHBOARD_CACHE_TTL`) | CRM, Projects, HRMS, Marketing, Analytics, Administration homes use `rememberHome()` |
| Widget preferences | Bypass cache for `widgetLayout`; cleared on preference update | Customize panel reflects immediately |
| Vite assets | Production build only — `npm run build` | No `npm run dev` in prod; `public/build/manifest.json` present |
| Queue | `QUEUE_CONNECTION=database` or `redis`; worker running | Pending jobs drain; no sustained backlog |
| Search | Provider-based; no full-table scan in hot paths | Palette/search respond < 2s on typical org data |
| DB | Eager loading on home aggregators | No N+1 in workspace home services |

```bash
npm ci && npm run build
php artisan config:cache
# Optional: profile a home route under load
```

---

## 4. Accessibility (WCAG AA intent)

Shared components enforce baseline accessibility. Full audit rules: [accessibility-implementation.md](../frontend/accessibility-implementation.md).

| Requirement | How enforced |
|-------------|--------------|
| Landmarks | `<main>`, `<nav>`, `<header>` in enterprise shell layouts |
| Labels | `x-forms.field` associates labels with inputs |
| Focus | Visible focus rings; modal/palette trap focus |
| Keyboard | Sidebar, dropdowns, modals, command palette operable without mouse |
| Screen readers | Flash/toast `aria-live`; icon buttons have `aria-label` |
| Contrast | Design tokens in `resources/css/app.css` / Tailwind theme |

Manual spot-check each workspace home + one CRUD flow before release.

---

## 5. Responsive verification

Shared layouts use Tailwind breakpoints (`sm`, `md`, `lg`, `xl`):

- [ ] Desktop (≥1280px): full sidebar + content
- [ ] Laptop (1024px): sidebar collapse/tooltip mode
- [ ] Tablet (768px): drawer sidebar; tables scroll horizontally
- [ ] Mobile (375px): single-column; no horizontal overflow on forms

Strategy: [responsive-strategy.md](../design/responsive-strategy.md).

---

## 6. Security

| Control | Status | Detail |
|---------|--------|--------|
| Tenant isolation | ✅ | `set.organization` / `ensure.organization` on tenant routes; org-scoped queries |
| Platform isolation | ✅ | Separate `platform` guard, session cookie, `/platform/*` routes |
| CSRF | ✅ | `@csrf` on web forms; `X-CSRF-TOKEN` meta in layouts |
| API auth | ✅ | Sanctum bearer tokens + org middleware |
| Rate limiting | ✅ | `throttle:api` on API v1; dedicated limiters for lead intake, webhooks, careers |
| Task attachments | ✅ | MIME allow-list in `config/attachments.php`; max 10 MB; validated in `StoreTaskAttachmentRequest` |
| Session cookie | ✅ | `SESSION_SECURE_COOKIE=true` on HTTPS; `SESSION_SAME_SITE=lax` |
| File uploads (CRM/HR) | ✅ | Entity-specific MIME/size in config |
| XSS | ✅ | Blade escaping; no `{!! !!}` on user content in standard views |
| Authorization | ✅ | RBAC permissions + policies per module |

Platform vs tenant sessions must never share cookies or guards. See [troubleshooting](../troubleshooting/overview.md#platform-vs-tenant-session).

---

## 7. End-to-end / smoke testing

**Automated (CI / pre-deploy):**

```bash
php artisan test --group=smoke
```

Covers: tenant workspace homes (200), guest redirect, `/up`, administration RBAC (employee forbidden).

**Manual:** [smoke.md](./smoke.md) — CRM lead create, project task, leave request, marketing campaign, analytics view, org settings, platform monitoring.

Broader suite: `php artisan test` (~400+ feature tests). Browser E2E framework is **out of scope** for 14.9.

---

## 8. Import / export validation

| Flow | Route / entry | MVP status |
|------|---------------|------------|
| Lead import | `leads.import.*` | ✅ CSV/XLSX wizard, preview, validation report |
| Customer import | `customers.import.*` | ✅ CSV/XLSX wizard |
| CRM export hub | `crm.exports` | ✅ Leads, customers, opportunities, invoices, etc. |
| Employee CSV import | — | ❌ Not in MVP |
| Project CSV import | — | ❌ Not in MVP |
| Recruitment exports | `hrms.recruitment.exports.index` | ✅ Report exports |
| Payroll bank export | `hrms.payroll.bank-exports.index` | ✅ |

Validate: upload template → preview errors → execute → summary counts match. Failed rows downloadable.

---

## 9. Notifications & automation

| Channel | Status | Notes |
|---------|--------|-------|
| Database (in-app) | ✅ Primary | Notification drawer; persisted in `notifications` table |
| Email | ✅ | Queued mail; payslip publication emails via queue job |
| Payslip resend | ✅ | `hrms.payroll.payslips.email` |
| Scheduled jobs | ✅ | Recurring tasks (hourly), recruitment retries (5 min), `schedule:heartbeat` (every minute) |
| Reminder rules | ⚠️ Advisory | Stored as free-text in org notification settings — **no cron executor** |
| Workflow triggers | ✅ | Domain events → workflow engine (module-specific) |

Queue worker must run in production. Failed jobs: `php artisan queue:failed` or Platform → Monitoring.

---

## 10. Logging & monitoring

| Signal | Access |
|--------|--------|
| Health | `GET /up` |
| Platform monitoring UI | `platform.monitoring.index` (queue, failed jobs, cache, DB, storage, log tail) |
| Failed jobs CLI | `php artisan queue:failed` · `queue:retry {id}` · `queue:flush` (caution) |
| Application logs | `storage/logs/laravel.log` (`LOG_STACK=daily` in prod) |
| Audit logs | In-app per module + platform audit |
| Scheduler liveness | `schedule:heartbeat` → `scheduler.last_heartbeat_at` cache key |

**Not bundled:** Laravel Telescope, Horizon. Use platform monitoring + OS/process supervisor.

Admin guide: [monitoring.md](../admin-guide/monitoring.md).

---

## 11. Documentation inventory

| Category | Path |
|----------|------|
| Getting started | [docs/getting-started/overview.md](../getting-started/overview.md) |
| Deployment | [docs/deployment/overview.md](../deployment/overview.md) |
| Upgrade | [UPGRADE.md](../../UPGRADE.md) |
| Production readiness | This file |
| Smoke / release | [smoke.md](./smoke.md) · [checklist.md](./checklist.md) |
| Troubleshooting | [docs/troubleshooting/overview.md](../troubleshooting/overview.md) |
| API | [docs/api/overview.md](../api/overview.md) |
| Frontend / components | [docs/frontend/](../frontend/) |
| CRM | [docs/crm/](../crm/) |
| HRMS | [docs/hrms/](../hrms/) |
| Projects | [docs/projects/](../projects/) |
| Platform admin | [docs/frontend/platform-administration.md](../frontend/platform-administration.md) |
| Org admin | [docs/frontend/organization-administration.md](../frontend/organization-administration.md) |
| Marketing / Analytics | [docs/frontend/marketing-analytics-workspace.md](../frontend/marketing-analytics-workspace.md) |
| Architecture | [docs/architecture/](../architecture/) |
| Phase progress | [docs/P14_PHASE_14_*_PROGRESS.md](../P14_PHASE_14_9_PROGRESS.md) |
| Release notes template | [docs/release-notes/overview.md](../release-notes/overview.md) |

In-app Knowledge Center (`/knowledge`) mirrors module docs when enabled.

---

## 12. Release management

| Item | Location |
|------|----------|
| Versioning | Dated Phase tags (14.9) + git tag when published |
| Upgrade procedure | [UPGRADE.md](../../UPGRADE.md) — forward migrations only |
| Rollback | Artifact restore + DB backup; UI-only: `ENTERPRISE_SHELL=false` |
| Release notes | [docs/release-notes/overview.md](../release-notes/overview.md) |
| Pre/post checklist | [checklist.md](./checklist.md) |
| Migration validation | `php artisan migrate:status` before and after deploy |

Never run `migrate:fresh`, `migrate:refresh`, or `db:wipe` against shared environments.

---

## 13. Production configuration checklist

See [deployment/overview.md](../deployment/overview.md) for full table. Minimum:

- [ ] `APP_ENV=production` · `APP_DEBUG=false` · `APP_URL` HTTPS
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `QUEUE_CONNECTION` set; worker supervised
- [ ] Cron: `* * * * * php /path/to/artisan schedule:run`
- [ ] `CACHE_STORE` redis or database
- [ ] Mail driver configured and test message sent
- [ ] Filesystem/S3 for uploads
- [ ] `SANCTUM_STATEFUL_DOMAINS` if SPA clients
- [ ] `ENTERPRISE_SHELL=true` (unless rollback)
- [ ] SSL at reverse proxy
- [ ] Database backups scheduled
- [ ] `.env` secrets not in git

---

## 14. Final QA checklist

### Regression

- [ ] Login / logout (tenant + platform)
- [ ] Organization switcher sets correct tenant context
- [ ] RBAC: employee cannot access admin-only routes
- [ ] CRM: lead → opportunity → quotation flow
- [ ] Projects: create project → task → attachment upload (allowed MIME)
- [ ] HRMS: leave request submission
- [ ] Marketing: campaign CRUD
- [ ] Analytics: executive dashboard loads
- [ ] Import: lead CSV with one invalid row → error report
- [ ] Notification drawer shows recent items

### Cross-browser (manual)

- [ ] Chrome / Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (if macOS clients)

### Multi-tenant

- [ ] User in Org A cannot read Org B records (API + UI)
- [ ] Platform operator cannot access tenant data without impersonation (if enabled)

### Sign-off

| Role | Name | Date | Pass |
|------|------|------|------|
| Engineering | | | |
| Operations | | | |
| Product | | | |

---

## Phase 14.9 outcome

Completion of this checklist marks NovaCRM **production-ready** for commercial onboarding. Core product development (Phase 14) is complete; subsequent work is customer operations, Wave 8 alias cleanup, and optional observability tooling (Telescope/Horizon).

Progress record: [P14_PHASE_14_9_PROGRESS.md](../P14_PHASE_14_9_PROGRESS.md).
