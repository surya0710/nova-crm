# Phase 14.1 Progress — Foundation, Application Shell & Core Component Platform

**Status:** Complete (foundation shipped)  
**Date:** 2026-07-24  
**Scope:** Production UI infrastructure only — no CRM/HRMS/Projects/Marketing page redesigns

---

## Outcome

NovaCRM now has a production Enterprise Application Shell implementing Phase 13.1–13.4 standards: design tokens, workspace-aware navigation, shared Blade UI library, theme engine, command palette / search / notification foundations, personalization storage, and an incremental migration layer.

---

## Deliverables

| # | Deliverable | Status | Notes |
|---|-------------|--------|-------|
| 1 | Design tokens | Done | `resources/css/themes/tokens.css` + Tailwind theme extend |
| 2 | Application shell | Done | `layouts/app.blade.php` AppShell (header, sidebar, content, footer) |
| 3 | Sidebar navigation | Done | `components/nav/sidebar` + MenuBuilder + workspace grouping |
| 4 | Header experience | Done | Search, palette, notifications, user menu, theme, org |
| 5 | Core Blade library | Done | `components/ui/*`, `forms/*`, `tables/*` |
| 6 | Page layout framework | Done | `components/layouts/*` (home, listing, detail, form, settings, analytics, dashboard) |
| 7 | Navigation services | Done | WorkspaceResolver, MenuBuilder, BreadcrumbBuilder, Favorites/Recents/Pinned, ContextManager |
| 8 | Theme engine | Done | Light/dark/system + org branding CSS vars + ThemeService |
| 9 | Accessibility | Done | Skip link, focus-visible, ARIA dialogs, semantic landmarks, reduced-motion tokens |
| 10 | Responsive framework | Done | Mobile drawer, lg sidebar, responsive header controls |
| 11 | Performance foundation | Done | Shared Vite assets, Alpine stores, deferred modal/drawer fetch |
| 12 | Command palette foundation | Done | UI + Ctrl/Cmd+K + provider registry |
| 13 | Global search foundation | Done | Search modal + scopes + SearchService provider |
| 14 | Notification center | Done | Drawer reusing Laravel notifications |
| 15 | Personalization foundation | Done | `user_ui_preferences` (theme, density, sidebar, workspace, favorites, recents) |
| 16 | Migration layer | Done | Feature flags + legacy button/input/sidebar-link aliases + shell rollback |

---

## Key paths

### Config
- `config/features.php` — `ENTERPRISE_SHELL`, palette, search, theme flags
- `config/navigation.php` — workspaces + permission-aware menus

### CSS / JS
- `resources/css/themes/tokens.css`
- `resources/css/app.css`
- `tailwind.config.js`
- `resources/js/stores/shell.js`

### Services
- `app/Services/Navigation/*`
- `app/Services/Theme/ThemeService.php`
- `app/Services/CommandPalette/*`
- `app/Services/Search/*`

### HTTP
- `app/Http/Controllers/Shell/*`
- Routes under `shell.*`

### Views
- `resources/views/layouts/app.blade.php`
- `resources/views/components/nav/*`
- `resources/views/components/shell/*`
- `resources/views/components/ui/*`
- `resources/views/components/forms/*`
- `resources/views/components/layouts/*`

### Data
- Migration `2026_07_24_140100_create_user_ui_preferences_table`
- Model `App\Models\UserUiPreference`

### Docs
- `docs/frontend/migration-progress.md`
- `docs/frontend/component-catalog.md`
- `docs/frontend/shell-implementation.md`

---

## Feature flags / rollback

| Env | Default | Effect |
|-----|---------|--------|
| `ENTERPRISE_SHELL` | `true` | New shell vs legacy sidebar include |
| `WORKSPACE_NAV` | `true` | Workspace switcher |
| `COMMAND_PALETTE` | `true` | Command palette |
| `GLOBAL_SEARCH_MODAL` | `true` | Search modal |
| `NOTIFICATION_DRAWER` | `true` | Notification drawer |
| `THEME_SWITCHER` | `true` | Theme cycle control |

Set `ENTERPRISE_SHELL=false` to roll back chrome while keeping tokens/components.

---

## Out of scope (confirmed untouched as redesigns)

Module page redesigns for CRM, Projects, HRMS, Marketing, Analytics, Reports, Recruitment, Finance, Support, Assets remain for later Phase 14.x waves. Existing pages continue to render inside the new shell via `<x-app-layout>`.

---

## Verification

- Migration applied: `user_ui_preferences`
- `php artisan route:list --name=shell` — 9 routes
- `php artisan view:cache` — success
- `npm run build` — success

---

## Next waves

Continue per `docs/frontend/migration-strategy.md`:

- Wave 2+: CRM listing/detail templates adopting `x-layouts.*` + `x-ui.*`
- Wave 3+: Projects workspace homes
- Wave 4+: HR grouped nav polish / My HR mode
- Entity search providers beyond `LegacySearchProvider`
