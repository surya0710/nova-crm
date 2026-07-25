# Smoke Test Guide — Phase 14.9

Fast validation that NovaCRM is alive after deploy or upgrade. Run automated smoke first, then manual checks for paths automation does not cover.

**Related:** [production-readiness.md](./production-readiness.md) · [checklist.md](./checklist.md) · [UPGRADE.md](../../UPGRADE.md) · [Deployment](../deployment/overview.md)

---

## Automated smoke

Requires test database (PHPUnit uses `RefreshDatabase` in isolation — **not** your dev/prod DB).

```bash
php artisan test --group=smoke
```

### What it covers

| Test | Assertion |
|------|-----------|
| Tenant workspace homes | `crm.home`, `projects.home`, `hrms.home`, `marketing.home`, `analytics.home`, `administration.home` → 200 for org owner |
| Guest access | Unauthenticated request to `crm.home` → redirect to login |
| Health endpoint | `GET /up` → 200 |
| RBAC | Employee role → `administration.home` → 403 |

Expected: all tests pass, 0 failures. Skips only if a route is not registered (should not happen in 14.9).

---

## Manual smoke — prerequisites

1. Application deployed with migrations applied (`php artisan migrate --force`).
2. Assets built (`npm run build`).
3. At least one organization with an **organization-owner** user.
4. Queue worker running if testing email or payslip queue.
5. `APP_URL` matches the URL you browse.

---

## 1. Health & infrastructure

| Step | Action | Expected |
|------|--------|----------|
| 1.1 | `curl -fsS "$APP_URL/up"` | HTTP 200, body indicates healthy |
| 1.2 | Confirm `public/build/manifest.json` exists on server | Vite assets compiled |
| 1.3 | Load any page; verify CSS/JS (no unstyled HTML) | Enterprise shell styled |
| 1.4 | `php artisan queue:work` running (or supervisor) | Process active |
| 1.5 | Trigger scheduler or wait 1 min; check heartbeat | `php artisan schedule:heartbeat` succeeds; Platform Monitoring shows scheduler note |

---

## 2. Tenant authentication & org context

| Step | Action | Expected |
|------|--------|----------|
| 2.1 | Open `/login`; sign in as org owner | Redirect to home/dashboard |
| 2.2 | Confirm organization name in header/context bar | Current org visible |
| 2.3 | Switch organization (if user belongs to multiple) | Data scope changes |
| 2.4 | Sign out | Session cleared; protected routes redirect |

**Failure hints:** [419 CSRF](../troubleshooting/overview.md#419-page-expired-csrf) · [org not set](../troubleshooting/overview.md#organization-not-set)

---

## 3. Workspace homes

Load each URL as org owner. Each should return 200 with widget grid (may show empty states — that is OK).

| Workspace | URL / route |
|-----------|-------------|
| CRM | `/crm` · `crm.home` |
| Projects | `/projects/home` · `projects.home` |
| HRMS | `/hrms` · `hrms.home` |
| Marketing | `/marketing` · `marketing.home` |
| Analytics | `/analytics` · `analytics.home` |
| Administration | `/administration` · `administration.home` |

Quick UX checks per home:

- [ ] Breadcrumbs or page title present
- [ ] Command palette opens (`Ctrl+K` / `⌘K`)
- [ ] Global search opens and returns results (or empty-state preset)
- [ ] No JavaScript console errors

---

## 4. Platform isolation

| Step | Action | Expected |
|------|--------|----------|
| 4.1 | Open `/platform/login` in **incognito** (or separate browser profile) | Platform login form (dark chrome) |
| 4.2 | Sign in as platform operator | Platform dashboard loads |
| 4.3 | Open `/crm` in same session | Redirect to tenant login (no cross-guard access) |
| 4.4 | In tenant session, open `/platform` | Redirect to platform login |
| 4.5 | Platform → Monitoring (`platform.monitoring.index`) | Queue/cache/DB snapshot visible |

**Failure hints:** [platform vs tenant session](../troubleshooting/overview.md#platform-vs-tenant-session)

---

## 5. Critical path spot checks

One happy path per module — stop at first failure and check logs.

| Module | Steps | Pass criteria |
|--------|-------|---------------|
| CRM | Leads index → Create lead → Save | Flash success; lead in list |
| Projects | Projects index → open project → Tasks tab | Project detail loads |
| HRMS | Leave → My requests (or equivalent) | Page loads; form accessible |
| Marketing | Campaigns index → Create (minimal fields) → Save | Campaign appears in list |
| Analytics | Executive analytics view | KPI cards/metrics render |
| Admin | Administration home → Users or Settings hub | Hub cards link correctly |

---

## 6. Import smoke (CRM)

| Step | Action | Expected |
|------|--------|----------|
| 6.1 | Download lead import CSV template (`leads.import.template.csv`) | File downloads |
| 6.2 | Upload template with one valid row | Preview shows row |
| 6.3 | Execute import | Summary: 1 imported |
| 6.4 | Upload file with one invalid email | Validation errors listed; no partial corrupt data |

Customer import: repeat at `imports/customers` if time permits.

---

## 7. Queue & notifications

| Step | Action | Expected |
|------|--------|----------|
| 7.1 | Perform action that queues notification (e.g. assign task, if configured) | Job appears in `jobs` table then drains |
| 7.2 | Open notification drawer in shell | Database notifications listed |
| 7.3 | (Optional) Publish payroll / resend payslip email with mail configured | Email queued; `queue:failed` empty |

If jobs stick: [queue stuck](../troubleshooting/overview.md#queue-worker-stuck-or-failed-jobs).

---

## 8. API spot check (optional)

```bash
# Create token in UI: Settings → API Tokens
curl -fsS -H "Authorization: Bearer {token}" \
     -H "Accept: application/json" \
     "$APP_URL/api/v1/leads?per_page=1"
```

Expected: JSON 200 with paginated leads (or empty array).

---

## Smoke duration targets

| Environment | Automated | Manual |
|-------------|-----------|--------|
| CI / staging | < 2 min | 15–20 min |
| Production post-deploy | Run automated against staging first | 10 min critical path only |

---

## When smoke fails

1. Capture HTTP status, URL, user role, organization ID.
2. Check `storage/logs/laravel.log` and Platform → Monitoring failed jobs.
3. Consult [troubleshooting/overview.md](../troubleshooting/overview.md).
4. Roll back per [UPGRADE.md](../../UPGRADE.md) if blocking.

Record outcome in [checklist.md](./checklist.md) sign-off section.
