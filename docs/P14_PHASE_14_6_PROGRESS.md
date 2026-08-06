# Phase 14.6 Progress — Platform Administration Workspace

**Status:** Complete  
**Date:** 2026-07-24  
**Scope:** Deliver the Platform Administration Workspace as the operational control center for NovaCRM SaaS, outside the tenant environment, on shared Enterprise UX components.

---

## Outcome

Platform Administration is the **SaaS owner console** at `/platform`, isolated via the `platform` auth guard and session cookie. It reuses existing organization, audit, impersonation, and report services, and extends backend only where platform capabilities were missing (subscriptions/billing records, licensing overrides, support tickets, announcements, monitoring, security policies, configuration store, global user ops, provider center, shell search/palette).

---

## Deliverables

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 1 | Platform Dashboard | Done | `PlatformWorkspaceHomeService` + widget personalization |
| 2 | Organization Management | Done | List/detail/create/edit + suspend/restore/delete/impersonate |
| 3 | Subscription & Billing | Done | Plans, active, trials, renewals, coupons, invoices, transactions |
| 4 | Licensing | Done | Plan builder overrides, modules, quotas |
| 5 | Global User Management | Done | Cross-tenant users, logins, sessions, MFA, lock, reset |
| 6 | Provider Center | Done | Health, validate, credential test |
| 7 | Monitoring | Done | Queue, jobs, cache, Redis, DB, storage, logs, health |
| 8 | Security Center | Done | Policies + security events |
| 9 | Audit Center | Done | Filters by category/search/org/date |
| 10 | Support Center | Done | Tickets, announcements, maintenance/broadcast |
| 11 | Platform Configuration | Done | Branding, domains, regional, AI, defaults, email templates |
| 12 | Search Integration | Done | `PlatformSearchService` scopes |
| 13 | Command Palette | Done | Platform-grouped commands |
| 14 | Empty States | Done | Presets for orgs/subscriptions/providers/tickets/plans/audit |
| 15 | Responsive | Done | Shared layouts + breakpoints |
| 16 | Accessibility | Done | Skip link, landmarks, labeled forms, dialogs |
| 17 | Documentation | Done | This file + platform-administration.md + catalog/progress |

---

## Architecture

```
/platform/*          auth:platform (PlatformUser)
layouts/platform     Enterprise tokens + command palette + global search
config/platform.php  roles, permissions, navigation, plans, providers
```

Tenant AppShell is **not** used. Platform gets a parallel shell that reuses `x-nav.command-palette` / `x-nav.global-search` pointed at `platform.shell.*` endpoints.

---

## Key paths

### Home
- Route: `platform.dashboard` → `/platform`
- Service: `app/Services/Platform/PlatformWorkspaceHomeService.php`
- View: `resources/views/platform/dashboard.blade.php`

### Shell
- `platform.shell.commands` / `platform.shell.search`
- `PlatformCommandPaletteService` / `PlatformSearchService`

### New / extended services
- `PlatformSubscriptionService`, `PlatformLicensingService`
- `PlatformProviderService`, `PlatformMonitoringService`
- `PlatformSecurityService`, `PlatformSupportService`
- `PlatformConfigurationService`, `PlatformGlobalUserService`
- Expanded `OrganizationManagementService`, `PlatformDashboardService`, `PlatformAuditService`

### Schema (additive)
- Migration `2026_07_24_140600_add_platform_administration_tables`
- `platform_users.preferences`, `locked_at`, `failed_login_attempts`
- `platform_support_tickets`, `platform_announcements`, `platform_coupons`
- `platform_billing_records`, `platform_settings`

---

## Verification

```bash
php artisan migrate
php artisan view:cache
php artisan route:list --name=platform.
php artisan test --filter=PlatformTest
```

Smoke: Platform Home widgets · Organizations lifecycle · Subscriptions/Plans · Licensing · Global Users · Providers · Monitoring · Security · Audit filters · Support tickets · Configuration · Search (⌘K) · Command palette.

---

## Out of scope (later waves)

Organization Administration (tenant), Configuration Hub (tenant), Marketing Workspace, Analytics Workspace, Customer Portal, Mobile Application, live payment-gateway charge flows, full SSO enforcement engines.
