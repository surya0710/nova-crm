# Deliverable 9 — Responsive Implementation Standards

Implementation rules for [../design/responsive-strategy.md](../design/responsive-strategy.md).

---

## Breakpoint usage

| Prefix | Min | Shell behavior |
|--------|-----|----------------|
| (none) | 0 | Mobile drawer; stacked |
| `sm:` | 640 | Minor spacing |
| `md:` | 768 | 2-col forms/widgets |
| `lg:` | 1024 | Persistent sidebar |
| `xl:` | 1280 | Side panels / attention rail |
| `2xl:` | 1536 | Wide dashboards |

---

## Desktop / laptop

- `lg:translate-x-0` pattern for sidebar visibility (as in current app layout)  
- `sidebarOpen` Alpine for mobile only  
- Collapsed rail: optional class on `body`/store  

---

## Tablet

- Prefer drawer or collapsed sidebar  
- Widget grids `md:grid-cols-2`  
- Filters in collapsible panel  

---

## Mobile

| Element | Implementation |
|---------|----------------|
| Menu button | Visible `lg:hidden` |
| Sidebar | Off-canvas + overlay; click overlay closes |
| Header actions | Icon buttons; search opens palette/sheet |
| Quick actions | Horizontal scroll `flex gap-2 overflow-x-auto` |
| Page padding | `p-4` minimum |

---

## Large displays

- Tables/kanban grow with main  
- Forms wrap in `max-w-3xl` / `max-w-5xl` — do not stretch inputs full 2xl width  

---

## Sidebar behavior (code)

- Single source of truth: Alpine `sidebarOpen` + CSS transforms  
- Focus: when opened on mobile, move focus into nav; on close restore  
- Escape closes  

---

## Dashboard stacking

Use ordered flex/grid; avoid `hidden md:block` that drops Attention entirely on mobile — stack instead.

---

## Tables

- Wrapper `overflow-x-auto`  
- Optional card list partial `@include` for `sm` down when UX requires  
- Sticky first column: careful `sticky left-0 bg-white`  

---

## Forms

```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
```

Stack labels above inputs always on mobile.

---

## Workspace navigation

- Drawer lists workspaces  
- Same components as desktop; accordion groups with Alpine  

---

## Testing widths

Verify 360 · 768 · 1280 · 1920 before claiming responsive done.

---

## Anti-patterns

- `display:none` of primary CTA on mobile without alternative  
- Hover-only row actions  
- Fixed `width: 1200px` containers  
