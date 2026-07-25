# Migration Progress

Tracks Phase 14 frontend migration against [migration-strategy.md](./migration-strategy.md).

---

## Wave status

| Wave | Scope | Status |
|------|-------|--------|
| **0** | Tokens, `ui.*` / `forms.*`, layout tokens | **Done** (14.1) |
| **1** | App shell, workspace switcher, nav split | **Done** (14.1 foundation) |
| **2** | CRM foundation + Leads reference | **Done** (14.2) |
| **2b** | CRM entity completion (Customers, Pipeline, Revenue, Activities, Reports, Imports) | **Done** (14.3) |
| **3** | Projects workspace (EPM home, nav, entities, search/palette) | **Done** (14.4) |
| **4** | HRMS & Recruitment workspace (home, nav, entities, search/palette) | **Done** (14.5) |
| **4b** | Platform Administration workspace (SaaS owner console) | **Done** (14.6) |
| **5** | Configuration Hub UI consolidation | Partial (hub reskin + org settings catalog expanded in 14.7) |
| **6** | Analytics / Admin homes + search providers | **Done** (14.7 Admin + **14.8 Analytics**) |
| **7** | Marketing MVP home | **Done** (14.8) |
| **8** | Polish: density UX, dark opt-in maturity, alias removal | Partial — auth login on `x-ui`/`x-forms` (14.9); legacy aliases remain in ~90 older views |

---

## Component alias map

| Legacy | New | Status |
|--------|-----|--------|
| `x-primary-button` | `x-ui.button variant=primary` | Aliased |
| `x-secondary-button` | `x-ui.button variant=secondary` | Aliased |
| `x-danger-button` | `x-ui.button variant=danger` | Aliased |
| `x-text-input` | `x-forms.input` | Aliased |
| `x-sidebar-link` | `x-nav.sidebar-link` | Aliased |
| `x-modal` | Prefer `x-ui.modal` for new work | Coexist |
| `layouts/sidebar.blade.php` | `x-nav.sidebar` when `ENTERPRISE_SHELL=true` | Dual path |

---

## Feature flags

See `config/features.php`. Production rollback: `ENTERPRISE_SHELL=false`.

---

## Notes

- Phase **14.2** delivered CRM workspace home, shared entity/activity/workspace components, and **Leads** as the reference module. See [crm-reference-implementation.md](./crm-reference-implementation.md).
- Phase **14.3** completed CRM migration: Customers, Opportunities (board+list), Revenue, Activities, Reports, Imports/Exports, Saved Views, expanded search/palette. See [../P14_PHASE_14_3_PROGRESS.md](../P14_PHASE_14_3_PROGRESS.md).
- Phase **14.4** completed Projects / EPM migration: workspace home, expanded nav, entity/task/portfolio/program/risk/resource chrome, budgets/reports hubs, Projects search providers + command palette. See [projects-workspace.md](./projects-workspace.md) and [../P14_PHASE_14_4_PROGRESS.md](../P14_PHASE_14_4_PROGRESS.md).
- Phase **14.5** completed HRMS & Recruitment migration: HR workspace home, expanded nav (incl. My HR/ESS), Employees reference module, Attendance/Leave/Recruitment/Performance/Payroll/Assets chrome, HR search providers + command palette, empty-state presets. See [hrms-workspace.md](./hrms-workspace.md) and [../P14_PHASE_14_5_PROGRESS.md](../P14_PHASE_14_5_PROGRESS.md).
- Phase **14.6** completed Platform Administration: SaaS owner console at `/platform` on Enterprise UX components, organization lifecycle, subscriptions/licensing, global users, providers, monitoring, security, audit, support, configuration, platform search/palette. Isolated from tenant workspaces via `platform` guard. See [platform-administration.md](./platform-administration.md) and [../P14_PHASE_14_6_PROGRESS.md](../P14_PHASE_14_6_PROGRESS.md).
- Phase **14.7** completed Organization Administration (tenant): workspace home, modules/security/branding/developer hubs, expanded admin nav + settings catalog, Admin search providers + command palette, Users/Config Hub/Notifications reskins, empty-state presets. See [organization-administration.md](./organization-administration.md) and [../P14_PHASE_14_7_PROGRESS.md](../P14_PHASE_14_7_PROGRESS.md).
- Phase **14.8** completed Marketing & Analytics BI: Marketing home/campaigns/attribution/providers, Analytics home + executive/CRM/Projects/HR analytics, AI insights (human review), KPI library, reports center, search/palette, empty states. See [marketing-analytics-workspace.md](./marketing-analytics-workspace.md) and [../P14_PHASE_14_8_PROGRESS.md](../P14_PHASE_14_8_PROGRESS.md).
- Phase **14.9** completed Enterprise Platform Finalization: production docs pack, workspace home caching, API throttle, attachment MIME hardening, smoke tests, scheduler heartbeat, env hygiene, auth login component migration. See [../release/production-readiness.md](../release/production-readiness.md) and [../P14_PHASE_14_9_PROGRESS.md](../P14_PHASE_14_9_PROGRESS.md).
- Prefer new components on any file touched; remove aliases only when `rg` shows zero legacy usage.
