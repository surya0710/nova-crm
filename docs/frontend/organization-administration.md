# Organization Administration Workspace

Phase **14.7** delivers the Konnect Nex **tenant** administration workspace. It runs inside the normal organization AppShell (Blade + Alpine + Vite), not the SaaS `/platform` console.

Use with [crm-reference-implementation.md](./crm-reference-implementation.md), [projects-workspace.md](./projects-workspace.md), [hrms-workspace.md](./hrms-workspace.md), and [platform-administration.md](./platform-administration.md) for shared Enterprise UX patterns and isolation rules.

---

## Entry

| Piece | Path |
|-------|------|
| Base URL | `/administration` |
| Home | `administration.home` |
| Layout | Tenant `x-app-layout` + `x-layouts.workspace-home` / `x-layouts.settings` |
| Nav | `config/navigation.php` â†’ `workspaces.administration` + `menus.administration` |
| Auth | Tenant `web` guard + `set.organization` |

---

## Workspace home

| Piece | Path |
|-------|------|
| Controller | `App\Http\Controllers\Administration\AdministrationHomeController` |
| Aggregator | `App\Services\Administration\AdministrationWorkspaceHomeService` |
| View | `resources/views/administration/home.blade.php` |
| Layout | `x-layouts.workspace-home` |

**Widgets:** Organization Summary Â· Modules Â· License Â· Storage Â· API Usage Â· Structure Â· Integrations Â· Security Status Â· Pending Invitations Â· Recent Activity Â· Attention rail Â· Quick Actions.

**Permission gate:** any of `settings.manage`, `users.view`, `rbac.view`, `workflows.view`, `metadata.view`, `metadata.manage`, `integrations.view`, `integrations.manage`, `api.tokens`, `audit.view`.

---

## Modules

| Area | Routes (prefix `administration.`) | Notes |
|------|-----------------------------------|-------|
| Home | `home` | Workspace overview |
| Modules | `modules.index` / `modules.update` | Plan modules (read-only) + feature toggles / visibility / landing pages |
| Security | `security.index` / `security.update` | Policies + login history from AuditLog |
| Branding | `branding.edit` / `branding.update` | Colors, email/login copy, logo |
| Developer | `developer.index` | API tokens, integrations, recruitment webhooks, rate-limit notes |

**Related tenant routes (unchanged):** `team.*`, `organization.settings.*`, `rbac.*`, `workflows.*`, `metadata-fields.*`, `integrations.*`, `api-tokens.*`, `audit-logs.*`.

---

## Search & command palette

Tenant shell endpoints (`/shell/*`):

| Provider | Key / group | Notes |
|----------|-------------|-------|
| `AdminCommandProvider` | Administration | Open Admin Home, Invite User, Create Department/Branch, Roles, Settings Hub, Security, Modules, Branding, Search Users/Departments |
| `AdminUserSearchProvider` | `users` | Org users by name/email |
| `AdminDepartmentSearchProvider` | `departments` | â†’ `hrms.departments.*` |
| `AdminBranchSearchProvider` | `branches` | â†’ `hrms.branches.*` |
| `AdminRoleSearchProvider` | `roles` | â†’ `rbac.roles.*` |
| `AdminSettingsSearchProvider` | `settings` | Labels from `config/organization_settings.php` |
| `AdminIntegrationSearchProvider` | `integrations` | `MarketingProviderService::integrationCardsForOrganization` |
| `AdminTemplateSearchProvider` | `templates` | RBAC + recruitment communication templates |

---

## Empty states

`x-ui.empty-state-preset` variants: `users` Â· `roles` Â· `integrations` Â· `api_tokens` Â· `departments` Â· `branches` Â· `admin_audit` Â· `settings` Â· `modules` Â· `security`.

---

## Isolation rules

1. Never register tenant admin modules under `/platform` or the `platform` guard.
2. Always authorize with tenant user permissions (`hasPermission` / policies).
3. Prefer additive `organization.settings` keys; do not wipe or reset the database for admin features.
4. Reuse existing Controllers/Services/Policies; no business-logic rewrites.

---

## Configuration catalog

`config/organization_settings.php` groups: organization Â· structure Â· hr_config Â· crm_config Â· project_config Â· security Â· platform.

New/expanded sections include modules, security, branding (`administration.branding.edit`), developer, users, audit, business hours, reporting structure.

---

## Organization profile (regional)

organization.edit preferences tab stores additive settings.regional:

| Key | Purpose |
|-----|---------|
| locale | BCP 47 language tag |
| iscal_year_start_month | 1–12 |
| date_format / 	ime_format | Display formats |

Working days / business hours remain at organization.settings.working-days.edit.