# Workspace & Navigation (Release 1.1.S.3 / S.3.2)

NovaCRM’s enterprise shell is workspace-driven: users switch between CRM, HRMS, Projects, Operations, and Administration from the header, land on their preferred workspace after login, and get faster access through search, favorites, recents, and workspace-scoped quick actions.

## Header workspace switcher

When `HEADER_WORKSPACE_SWITCHER=true` (default), the workspace selector appears in the application header instead of the sidebar.

Features:

- Searchable workspace list (SSR rows; search filters, never replaces)
- Favorite workspaces (star toggle, persisted per user in `user_ui_preferences.meta.favorite_workspaces`)
- Recent workspaces (updated on each switch)
- Keyboard navigation in the dropdown (arrow keys + Enter)
- Persists `last_workspace` via `POST /shell/workspace`

Disable with `HEADER_WORKSPACE_SWITCHER=false` to restore the sidebar-only switcher.

## Login & landing resolution (S.3.2)

After login (and `/` when authenticated), `NavigationService::resolveLandingUrl()` chooses the destination in this order:

1. **User preferred workspace** — `user_ui_preferences.default_workspace`
2. **User landing page** (legacy) — `landing_page` route name, if set and accessible
3. **Persona default workspace** — `config/navigation.php` → `persona_default_workspaces` (Employee→HRMS, Sales→CRM, …)
4. **Organization default workspace** — `organizations.settings.default_workspace` (used when persona workspace is unavailable)
5. **Last active workspace** — `last_workspace` (fallback only)
6. **System default** — `config/modules.php` → `default_workspace` (usually `crm`)

Persona is resolved before organization default so provisioned org `default_workspace=crm` does not send every employee to CRM.
Generic `/dashboard` is **not** used as the owner/default persona landing. Users may still open Personal Home manually.

Set default workspace via `PATCH /shell/preferences` with `default_workspace`.

Personas: owner, admin, manager, HR, employee, sales, project manager, reports.

## Workspace-scoped quick actions (S.3.2)

`ShellQuickActionService` (via `NavigationService::quickActions`) returns:

```php
['primary' => [...], 'overflow' => [...], 'all' => [...]]
```

- Actions come only from `config/navigation.php` → `quick_actions.{workspace}`
- Sorted by `group` (primary first) then `priority`
- Max visible primary: `quick_action_limits.primary` (default **5**)
- Header shows primary buttons + **More Actions** overflow (mobile: icon → bottom drawer)
- Permission and route existence filter applied in the service — not in controllers

## Workspace homes

Each licensed workspace exposes a dashboard-style home:

| Workspace | Route |
|-----------|-------|
| CRM | `crm.home` |
| Projects | `projects.home` |
| HRMS | `hrms.home` |
| Recruitment | `hrms.recruitment.dashboard` |
| Operations | `operations.home` |
| Marketing | `marketing.home` |
| Reports | `analytics.home` |
| Administration | `administration.home` |

Home pages consume the same `ShellQuickActionService` payload for their action bars.

## Shell APIs

| Method | Route | Purpose |
|--------|-------|---------|
| PATCH | `/shell/preferences` | Theme, density, sidebar, `default_workspace`, landing |
| POST | `/shell/workspace` | Remember last workspace |
| POST | `/shell/favorites` | Toggle favorite page |
| GET | `/shell/search` | Global search |
| GET | `/shell/notifications` | Notification drawer |

## Single source of truth

All shell UI must go through `NavigationService` — do not read `config/navigation.php` or call `WorkspaceResolver` from Blade/controllers for workspace lists, menus, quick actions, or landing URLs.
