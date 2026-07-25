# Shell Implementation

Enterprise Application Shell delivered in Phase 14.1. Product blueprints: [../product/sidebar-blueprint.md](../product/sidebar-blueprint.md), [../design/layout-system.md](../design/layout-system.md).

---

## Composition

```
<html data-theme data-density [brand CSS vars]>
  skip link
  impersonation banner
  AppShell
    Sidebar (workspace switcher · favorites · pinned · menu · recents · admin · user)
    Column
      Header (collapse · workspace title · search · help · notifications · theme · org · user menu)
      optional Context bar
      Main #main-content
      Footer
  Command palette · Global search · Notification drawer
```

Entry layout: `resources/views/layouts/app.blade.php` via `<x-app-layout>`.

---

## Navigation pipeline

1. `config/navigation.php` — workspace + menu definitions  
2. `MenuBuilder` — permission + route existence filter  
3. `WorkspaceResolver` — available workspaces + current from route / preference  
4. `NavigationContextManager` — assembles favorites, recents, pinned, theme prefs  
5. `ShellComposer` — injects `shellNav` into layout views  

---

## Keyboard shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl/Cmd + K` | Open global search |
| `Ctrl/Cmd + Shift + K` | Open command palette |
| `Esc` | Close overlay |

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
- `prefers-reduced-motion` zeroes motion tokens  

---

## Rollback

`ENTERPRISE_SHELL=false` restores legacy `layouts/sidebar` chrome while keeping tokens and `ui.*` components available.
