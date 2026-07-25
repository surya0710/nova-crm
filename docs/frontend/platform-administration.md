# Platform Administration Workspace

Phase **14.6** delivers the NovaCRM SaaS owner console. It is **outside the tenant environment** and uses the `platform` auth guard (`PlatformUser`), not the tenant AppShell.

Use with [crm-reference-implementation.md](./crm-reference-implementation.md), [projects-workspace.md](./projects-workspace.md), and [hrms-workspace.md](./hrms-workspace.md) for shared Enterprise UX patterns.

---

## Entry

| Piece | Path |
|-------|------|
| Base URL | `/platform` |
| Login | `platform.login` |
| Home | `platform.dashboard` |
| Layout | `resources/views/layouts/platform.blade.php` via `<x-platform-layout>` |
| Nav | `config/platform.php` → `navigation` |
| Auth | Guard `platform`, cookie `PLATFORM_SESSION_COOKIE` |

---

## Workspace home

| Piece | Path |
|-------|------|
| Controller | `App\Http\Controllers\Platform\DashboardController` |
| Aggregator | `App\Services\Platform\PlatformWorkspaceHomeService` |
| Metrics | `App\Services\Platform\PlatformDashboardService` |
| View | `resources/views/platform/dashboard.blade.php` |
| Layout | `x-layouts.workspace-home` |

**Widgets:** Total / Active / Trial / Expired Organizations · Active Users · MAU · Revenue · Subscriptions · Storage · Queue Health · Background Jobs · API Requests · Email Delivery · Provider Health · Platform Alerts · Recent Activity · Quick Actions.

**Personalization:** `PlatformUser.preferences.dashboard_layout.platform` via `POST platform.dashboard.widgets`.

---

## Modules

| Area | Routes (prefix `platform.`) | Notes |
|------|-----------------------------|-------|
| Organizations | `organizations.*` | Profile, subscription, usage, storage, modules, admins, activity, audit; suspend/restore/delete/impersonate |
| Subscriptions | `subscriptions.*`, `plans.*`, `coupons.*`, `invoices.*`, `transactions.*` | Plan assign, upgrade/downgrade, trials |
| Licensing | `licensing.*` | Plan builder overrides, module assignment, quotas |
| Users | `global-users.*`, `users.*` | Cross-tenant users + platform staff |
| Providers | `providers.*` | Google, Microsoft, Meta, SMTP, WhatsApp, SMS, Payment, AI |
| Monitoring | `monitoring.index` | Queue, failed jobs, scheduler, cache, Redis, DB, storage, logs |
| Security | `security.*` | MFA/password/session policies, events |
| Audit | `audit.index` | Category + search + org + date filters |
| Support | `support.*` | Tickets, announcements, maintenance, broadcasts |
| Configuration | `configuration.*` | Branding, domains, email templates, defaults, AI, regional |
| Templates / Reports | `industry-templates.*`, `reports.*` | Existing, restyled |

---

## Search & command palette

Platform shell endpoints (not tenant `/shell/*`):

| Endpoint | Purpose |
|----------|---------|
| `GET platform/shell/search` | Global search |
| `GET platform/shell/commands` | Command palette |

**Search scopes:** Organizations · Users · Subscriptions · Plans · Providers · Tickets · Coupons · Audit.

**Palette group:** Platform — Create Organization, Open Organizations/Subscriptions/Monitoring/Providers/Support, Search Organizations/Users.

---

## Empty states

`x-ui.empty-state-preset` variants: `organizations` · `subscriptions` · `providers` · `tickets` · `plans` · `platform_audit`.

---

## Isolation rules

1. Never register platform modules in `config/navigation.php` tenant workspaces.
2. Always authorize with `Gate::forUser(auth('platform')->user())`.
3. Prefer extending `App\Services\Platform\*` over tenant services.
4. Keep views on `<x-platform-layout>` + shared `x-ui.*` / `x-layouts.*` / `x-forms.*`.

---

## Related docs

- Progress: [../P14_PHASE_14_6_PROGRESS.md](../P14_PHASE_14_6_PROGRESS.md)
- Product workspaces: [../product/workspaces.md](../product/workspaces.md)
- Shell patterns: [./shell-implementation.md](./shell-implementation.md)
