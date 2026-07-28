# Shell Implementation

Enterprise Application Shell (Phase 14.1) with **Release 1.1.S.3.1** UX stabilization. Product blueprints: [../product/sidebar-blueprint.md](../product/sidebar-blueprint.md), [../design/layout-system.md](../design/layout-system.md).

---

## Composition

```
<html data-theme data-density [brand CSS vars]>
  skip link
  impersonation banner
  .nova-shell (h-full, overflow hidden — no page-level scroll)
    Fixed sidebar (.nova-shell-sidebar) — Logo → Pinned → Workspace nav → Admin → Profile
    Desktop spacer (matches sidebar width)
    .nova-shell-main (flex column, overflow hidden)
      Sticky header (.nova-header) — ☰ · Workspace switcher · Search · Quick actions · … · Profile
      optional Context bar
      Scrollable main (.nova-shell-content > .nova-content)
      Footer (shrink-0)
  Command palette · Global search · Notification drawer
```

Entry layout: `resources/views/layouts/app.blade.php` via `<x-app-layout>`.

**Scroll contract:** only `.nova-shell-content` scrolls. Header and sidebar stay fixed. Avoid `100vw` and nested scroll regions that cause horizontal overflow.

---

## Navigation pipeline (SSOT)

1. `config/navigation.php` — workspace + menu + search scopes + quick actions  
2. `NavigationService` — **only** source of truth for shell nav (`forShell`, menus, workspaces, quick actions, search scope)  
3. `WorkspaceResolver` — current workspace from **route first**, then preference  
4. `NavigationContextManager` — thin adapter around `forShell` / `rememberWorkspace`  
5. `ShellComposer` — injects `shellNav` **once** on `layouts.app`  

Blade receives plain arrays in `$shellNav`. Do not hardcode workspace lists in views.

---

## Workspace switcher

Component: `x-nav.header-workspace-switcher`

- Workspace rows are **server-rendered** (icons, active state, favorites)  
- Search sits **below** the list and **filters** rows — it never replaces them  
- Alpine handles open/close, filter query, Escape / outside click, favorite toggle  
- Feature flags: `workspace_nav` + `header_workspace_switcher`

---

## Header / search / quick actions

Order: menu toggle · workspace switcher · global search · **primary quick actions** · **More Actions** · help · notifications · theme · profile.

- Search placeholder and default scope follow `currentWorkspace`
- Quick actions: `NavigationService::quickActions` → `{ primary, overflow, all }` from `config/navigation.php`
- Max primary buttons: `quick_action_limits.primary` (default 5); tablet shows fewer; mobile uses icon + drawer
- Shortcut: `Ctrl/Cmd + K` opens global search

---

## Dashboard layout helpers

CSS utilities in `resources/css/app.css`:

| Class | Role |
|-------|------|
| `.dashboard-stack` | Vertical rhythm between sections |
| `.dashboard-kpis` | KPI row grid |
| `.dashboard-primary` | Main + aside columns |
| `.dashboard-primary-main` / `-aside` | Hierarchy split |

Workspace homes should use these classes via `x-layouts.workspace-home`.

---

## Keyboard shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl/Cmd + K` | Open global search |
| `Ctrl/Cmd + Shift + K` | Open command palette |
| `Esc` | Close overlay / workspace switcher |

---

## Theme engine

- User preference: `light` | `dark` | `system` in `user_ui_preferences`  
- Applied as `html[data-theme]`  
- Org branding via `organization.settings.branding.primary_color` / `accent_color` → CSS variables  
- Density: `comfortable` | `compact` → `html[data-density]`  

Service: `App\Services\Theme\ThemeService`.

---

## Shell API routes

| Method | Route | Purpose |
|--------|-------|---------|
| PATCH | `/shell/preferences` | Theme, density, sidebar, landing |
| POST | `/shell/workspace` | Remember workspace |
| POST | `/shell/favorites` | Toggle favorite |
| POST/DELETE | `/shell/recents` | Record / clear recents |
| GET | `/shell/commands` | Palette commands |
| POST | `/shell/commands/recent` | Record command use |
| GET | `/shell/search` | Modal search (+ scopes) |
| GET | `/shell/notifications` | Drawer payload |

---

## Extensibility

### Command providers

Implement `CommandProviderInterface` and register on `CommandPaletteRegistry` in `AppServiceProvider`.

### Search providers

Implement `SearchProviderInterface` and register on `SearchProviderRegistry`. Default: `LegacySearchProvider` wrapping `SearchService`.

---

## Accessibility

- Skip to content link  
- `:focus-visible` rings via tokens  
- Dialog overlays with `role="dialog"` / `aria-modal`  
- Landmark regions (`nav`, `main`, `header`, `footer`)  
- Workspace switcher: `listbox` / `option`, Escape, outside click  
- `prefers-reduced-motion` zeroes motion tokens  

---

## Rollback

`ENTERPRISE_SHELL=false` restores legacy `layouts/sidebar` chrome while keeping tokens and `ui.*` components available.
