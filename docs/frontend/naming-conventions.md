# Deliverable 10 — Frontend Naming Conventions

Naming standards across Blade, JS, CSS, and assets.

---

## Blade components

| Item | Convention | Example |
|------|------------|---------|
| File | kebab-case | `page-header.blade.php` |
| Nested tag | dot path | `<x-nav.sidebar-link>` |
| Slots | kebab or camel | `actions`, `page-title` |
| Props | camelCase | `showIcon`, `variant` |
| Boolean props | positive names | `disabled` not `isNotEnabled` |

Domain composites: `lead-convert-modal` under `domain/`.

---

## JavaScript

| Item | Convention |
|------|------------|
| Files | kebab-case `follow-up-alerts.js` or camel `commandPalette.js` — pick one per folder; **prefer kebab for features** |
| Alpine.data names | camelCase `dropdownMenu` |
| Alpine.store names | camelCase `commandPalette` |
| Events | `nova:entity:action` |
| Constants | SCREAMING_SNAKE only for true constants |

---

## Assets

| Item | Convention |
|------|------------|
| Images | kebab-case `empty-no-leads.svg` |
| Public build | Vite hashed (do not hand-name) |
| Feature CSS | kebab-case matching feature |

---

## Icons

| Item | Convention |
|------|------------|
| Icon component name prop | Heroicon-like `user`, `plus`, `trash` |
| SVG file | `icon-user.svg` if file-based |
| Blade include | Prefer component over raw dump |

---

## CSS variables

```text
--nova-{category}-{name}[-{variant}]
--nova-color-primary-600
--nova-space-4
--nova-z-modal
```

No `--blue` or `--brand1` without mapping to semantic tokens.

---

## Tailwind / @layer

| Item | Convention |
|------|------------|
| Component classes | `.nova-card`, `.nova-btn` prefix `nova-` |
| Landing-only | Keep existing `.landing-*` out of app shell |

---

## Workspace identifiers

| Context | Convention | Example |
|---------|------------|---------|
| Slugs | kebab-case | `crm`, `projects`, `hr`, `administration` |
| Blade vars | camelCase | `$workspace`, `$workspaceSlug` |
| data attrs | `data-workspace="crm"` | |
| Route prefixes | existing Laravel names | do not rename casually |

Match [../product/workspaces.md](../product/workspaces.md).

---

## Entity components

| Pattern | Example |
|---------|---------|
| Panel | `{entity}-panel` → `tasks-panel` |
| Modal | `{entity}-{action}-modal` → `lead-convert-modal` |
| Row | Prefer shared table columns over `{entity}-row` unless unique |

---

## PHP View Components

- Class: `App\View\Components\Nav\SidebarLink`  
- View: `components.nav.sidebar-link`  

---

## Anti-patterns

- Mixing `LeadConvertModal.blade.php` Studly with kebab tags  
- `data-id` without context (`data-lead-id`)  
- CSS variables colliding with Bootstrap `--bs-*` unless scoped  
