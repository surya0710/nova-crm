# Workspace & Navigation (Release 1.1.S.3)

NovaCRM’s enterprise shell is workspace-driven: users switch between CRM, HR, Projects, Operations, and Administration from the header, land on persona-appropriate home pages after login, and get faster access through search, favorites, recents, and quick actions.

## Header workspace switcher

When `HEADER_WORKSPACE_SWITCHER=true` (default), the workspace selector appears in the application header instead of the sidebar.

Features:

- Searchable workspace list
- Favorite workspaces (star toggle, persisted per user in `user_ui_preferences.meta.favorite_workspaces`)
- Recent workspaces (updated on each switch)
- Keyboard navigation in the dropdown (arrow keys + Enter)
- Persists `last_workspace` via `POST /shell/workspace`

Disable with `HEADER_WORKSPACE_SWITCHER=false` to restore the sidebar-only switcher.

## Persona-based landing pages

After login, `NavigationService::resolveLandingUrl()` chooses the destination in this order:

1. User preference `landing_page` (route name)
2. Persona route from org `default_landing_pages` or `config/modules.php`
3. Organization `default_workspace` (workspace home URL)
4. `last_workspace` preference
5. First available workspace / dashboard fallback

Personas are resolved from permissions (owner, admin, manager, HR, employee, sales, project manager).

Configure defaults in `config/navigation.php` (`persona_landing_pages`) and per-organization overrides in `organizations.settings.default_landing_pages`.

## Workspace homes

Each licensed workspace exposes a dashboard-style home:

| Workspace | Route |
|-----------|-------|
| CRM | `crm.home` |
| Projects | `projects.home` |
| HR | `hrms.home` |
| Operations | `operations.home` |
| Marketing | `marketing.home` |
| Analytics | `analytics.home` |
| Administration | `administration.home` |

Homes use the Dashboard Platform widgets and workspace-specific services under `app/Services/*/ *WorkspaceHomeService.php`.

## Global search

- Modal: `Ctrl+K` (command palette: `Ctrl+Shift+K`)
- API: `GET /shell/search?q=&scope=`
- Default scope follows the active workspace (`config/navigation.php` → `workspace_search_scopes`)
- Recent searches stored in `user_ui_preferences.recent_searches`

## Favorites & recents

| Feature | Storage | API |
|---------|---------|-----|
| Favorite pages | `favorites` JSON | `POST /shell/favorites` |
| Favorite workspaces | `meta.favorite_workspaces` | `POST /shell/workspace-favorites` |
| Recent pages | `recent_pages` JSON | Auto via `RecordRecentPage` middleware |
| Recent workspaces | `meta.recent_workspaces` | Updated on workspace switch |

Recent page recording is configured in `config/navigation.php` → `recents.record_patterns` and `recents.route_labels`.

## Quick actions

Workspace-scoped quick actions appear in the header (`config/navigation.php` → `quick_actions`). Actions are filtered by route availability and Dynamic RBAC permissions.

## Breadcrumbs

Use `x-nav.breadcrumbs` with workspace → section → record hierarchy. Separator is `>` (workspace context first).

`BreadcrumbBuilder::fromWorkspace()` is available for consistent generation in controllers.

## Shell preferences API

| Method | Endpoint | Purpose |
|--------|----------|---------|
| PATCH | `/shell/preferences` | Theme, density, sidebar, landing page |
| POST | `/shell/workspace` | Switch workspace |
| POST | `/shell/workspace-favorites` | Toggle favorite workspace |
| POST | `/shell/favorites` | Toggle favorite page |
| POST | `/shell/recents` | Record recent page (manual) |
| DELETE | `/shell/recents` | Clear recents |

## Testing

```bash
php artisan test --filter=ShellNavigationTest
```

## Related configuration

- `config/features.php` — `enterprise_shell`, `workspace_nav`, `header_workspace_switcher`
- `config/navigation.php` — workspaces, menus, personas, quick actions, recents
- `config/modules.php` — `default_landing_pages`
