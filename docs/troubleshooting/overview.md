# Troubleshooting Overview

Cross-cutting issues for Konnect Nex production and staging. Module-specific guides live under `docs/crm/troubleshooting/`, `docs/hrms/`, etc.

**Related:** [Deployment](../deployment/overview.md) · [Smoke tests](../release/smoke.md) · [Monitoring](../admin-guide/monitoring.md) · [UPGRADE.md](../../UPGRADE.md)

---

## 419 Page Expired (CSRF)

### Symptoms

- Form submit returns **419 Page Expired**
- AJAX POST fails with 419
- Occurs after idle tab, deploy, or load balancer switch

### Causes

- Session expired while page was open
- Missing or stale CSRF token
- Cookie not sent (domain / SameSite / secure mismatch)
- Multiple tabs logged in as different users

### Resolution

1. Refresh the page and resubmit (new CSRF token in meta tag and form).
2. Confirm `@csrf` on every POST form and `X-CSRF-TOKEN` header on fetch/Alpine calls.
3. Verify `APP_URL` matches the browser URL (scheme + host).
4. Check session driver (`SESSION_DRIVER=file` or `database`) is writable.
5. After deploy: `php artisan config:clear` if env changed.

### Prevention

- Keep session lifetime reasonable (`SESSION_LIFETIME`, default 120 min).
- Avoid caching HTML pages with embedded tokens at CDN edge.

---

## Session cookie not persisting

### Symptoms

- Login succeeds then immediately redirects back to login
- “Remember me” has no effect
- Works on HTTP but fails on HTTPS (or vice versa)

### Causes

- `SESSION_SECURE_COOKIE=true` on HTTP (local without TLS)
- `SESSION_DOMAIN` mismatch (subdomain vs apex)
- `SameSite=strict` blocking cross-site OAuth return
- Proxy not forwarding `X-Forwarded-Proto`

### Resolution

| Environment | Setting |
|-------------|---------|
| Local HTTP (XAMPP) | `SESSION_SECURE_COOKIE=false` |
| Production HTTPS | `SESSION_SECURE_COOKIE=true` |
| Subdomain app | `SESSION_DOMAIN=.yourdomain.com` (leading dot) or leave null for host-only |

Trust proxies in Laravel if behind nginx/Apache/load balancer. Clear browser cookies for the domain after env changes.

See [deployment/overview.md](../deployment/overview.md#production-configuration).

---

## Vite assets missing (unstyled page)

### Symptoms

- Page loads without CSS/JS
- Browser 404 on `/build/assets/*`
- Console: failed to load module / manifest missing

### Causes

- `npm run build` not run on deploy
- `public/build/` not deployed with artifact
- `ASSET_URL` incorrect for subdirectory installs (XAMPP `/nova-crm/public`)
- Running `npm run dev` in production without Vite dev server

### Resolution

```bash
npm ci && npm run build
```

Verify `public/build/manifest.json` exists. For subdirectory hosting:

```env
APP_URL=https://example.com/nova-crm/public
ASSET_URL=https://example.com/nova-crm/public
```

Then `php artisan config:cache`. Never rely on `npm run dev` in production.

---

## Queue worker stuck or failed jobs

### Symptoms

- Emails never sent (payslips, notifications)
- Jobs table row count grows indefinitely
- Platform Monitoring shows failed jobs or “busy” queue

### Causes

- No queue worker process
- Worker crashed after deploy (stale code)
- Job exception (mail misconfig, missing class)
- `QUEUE_CONNECTION=sync` in production (blocks request thread)

### Resolution

1. Start or restart worker:

   ```bash
   php artisan queue:restart
   php artisan queue:work --sleep=1 --tries=3 --timeout=360
   ```

2. List failures:

   ```bash
   php artisan queue:failed
   php artisan queue:retry {id}
   ```

3. Fix root cause in logs (`storage/logs/laravel.log`), then retry.
4. Platform → Monitoring for queue depth snapshot.

Use supervisor/systemd in production — not manual terminal sessions.

Details: [admin-guide/monitoring.md](../admin-guide/monitoring.md).

---

## Organization not set

### Symptoms

- Redirect to organization picker or “select organization”
- Empty data despite valid login
- API returns 403 / organization context error

### Causes

- User not member of any organization
- Session missing `current_organization_id`
- API call missing `X-Organization-Id` header

### Resolution

1. Tenant user: pick organization from switcher or complete onboarding invite.
2. Confirm membership in `organization_user` (or equivalent pivot).
3. API: send `X-Organization-Id: {id}` with Sanctum token.
4. Clear session and log in again if switcher state is corrupt.

---

## Platform vs tenant session

### Symptoms

- Logged into platform but tenant routes show wrong user or fail
- Tenant login exposes platform console without re-auth
- Cookie conflicts between `/` and `/platform`

### Causes

Konnect Nex uses **separate guards** and session cookies for tenant (`web`) and platform (`platform`). Mixing tabs or shared browser profiles causes confusion.

### Resolution

1. Use **separate browser profiles** or incognito for platform vs tenant testing.
2. Platform URLs: `/platform/*` only with `platform` guard login at `/platform/login`.
3. Tenant URLs: `/login` — never assume platform session applies.
4. Sign out of both before switching test personas.

Architecture: [platform-administration.md](../frontend/platform-administration.md).

---

## Failed migrations

### Symptoms

- `php artisan migrate` stops with SQL error
- `migrate:status` shows Pending with partial apply
- App boots but missing columns/tables

### Causes

- Drift between code and database (manual SQL edits)
- Running migrations out of order across branches
- Insufficient DB user privileges

### Resolution

1. **Do not** run `migrate:fresh` or `db:wipe` on shared data.
2. Read the migration error; fix forward with a new migration if schema partially applied.
3. Restore DB backup if deploy is blocked and data is at risk.
4. On staging first: `php artisan migrate --force` and verify.

Forward-only policy: [UPGRADE.md](../../UPGRADE.md).

---

## Marketing OAuth / provider connection failures

### Symptoms

- Meta/Google Ads connect button fails
- Redirect URI mismatch error
- Provider health “disconnected” on Marketing → Providers

### Causes

- OAuth redirect URL not registered with provider
- Platform-level credentials missing in `.env` or Platform → Providers
- Token expired; refresh failed
- `APP_URL` HTTP vs HTTPS mismatch in callback URL

### Resolution

1. Register exact callback URL from Konnect Nex provider settings with the ad platform.
2. Confirm platform credentials (not tenant) for SaaS-level OAuth where applicable.
3. Reconnect provider; check `storage/logs/laravel.log` for OAuth exceptions.
4. Marketing attribution still works for manual UTM; ads sync is optional for core CRM.

Docs: [api/marketing/overview.md](../api/marketing/overview.md).

---

## ENTERPRISE_SHELL rollback

### Symptoms

- Sidebar/header regression after frontend release
- Layout broken on specific workspace
- Need immediate UI stabilization without code rollback

### Resolution

```env
ENTERPRISE_SHELL=false
```

```bash
php artisan config:clear
# or php artisan config:cache after env update
```

This restores legacy chrome while keeping tokenized CSS and `x-ui.*` components available. Re-enable after fix:

```env
ENTERPRISE_SHELL=true
```

Also see `WORKSPACE_NAV`, `COMMAND_PALETTE`, `GLOBAL_SEARCH_MODAL` in `config/features.php`.

Full procedure: [UPGRADE.md](../../UPGRADE.md#feature-flag-rollback-ui).

---

## Quick diagnostic commands

```bash
php artisan about
php artisan migrate:status
php artisan queue:failed
php artisan route:list --name=crm.home
curl -fsS "$APP_URL/up"
tail -n 50 storage/logs/laravel.log
```

For operational monitoring: [monitoring.md](../admin-guide/monitoring.md).

---

## Escalation

| Severity | Action |
|----------|--------|
| P1 — outage / data leak | Roll back deploy; restore backup; incident channel |
| P2 — module broken | Feature flag rollback; hotfix forward migration |
| P3 — cosmetic / docs | Track in backlog; Wave 8 cleanup |

Record production incidents with timestamp, env, user role, org ID, and relevant log excerpts.
