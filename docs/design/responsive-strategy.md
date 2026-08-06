# Deliverable 15 — Responsive Strategy

How NovaCRM adapts across viewports.

---

## Breakpoints

| Name | Min width | Typical device |
|------|-----------|----------------|
| base | 0 | Phones |
| sm | 640px | Large phones |
| md | 768px | Tablets |
| lg | 1024px | Laptops — **sidebar persistent** |
| xl | 1280px | Desktops |
| 2xl | 1536px | Large displays |

---

## Desktop (≥ lg)

- Sidebar expanded or user-collapsed  
- Multi-column dashboards  
- Tables full  
- Entity detail: main + side  
- Hover affordances OK as supplement  

---

## Laptop (lg–xl)

- Same as desktop; watch widget overcrowding — rely on defaults ≤ 8 widgets  
- Optional attention rail stacks if width tight  

---

## Tablet (md–lg)

- Sidebar as drawer or collapsed rail  
- Dashboard widgets 1–2 columns  
- Filters collapse into sheet  
- Kanban: horizontal scroll OK  

---

## Mobile (< md)

| Area | Behavior |
|------|----------|
| Nav | Hamburger → drawer |
| Chrome | Compact search/bell/user |
| Dashboard | Stack KPIs; widgets full width |
| Quick Actions | Sheet / horizontal scroll chips |
| Tables | Card list or prioritized columns + scroll |
| Forms | Single column; sticky submit |
| Tabs | Scroll chips |
| Modals | Full-screen sheets |

Touch targets 44px; no hover-only actions.

---

## Large displays (2xl+)

- Main content may use full width for tables/kanban/gantt  
- Forms remain `container-lg` centered or left-aligned — do not stretch inputs to 2000px  
- Dashboards can use wider grids; max useful widget span still 12 of main  

---

## Navigation adaptation

| Viewport | Pattern |
|----------|---------|
| lg+ | Persistent sidebar |
| < lg | Drawer |
| Collapsed | Icon rail (user preference, desktop) |

Workspace switcher always reachable.

---

## Dashboard stacking

Order on mobile:

1. Header  
2. Attention  
3. Quick Actions  
4. KPIs  
5. Widgets (as saved order)  
6. Activity  

---

## Tables

See [table-standards.md](./table-standards.md) responsive section.

---

## Forms

Single column; date pickers usable with virtual keyboard; avoid horizontal field pairs below md.

---

## Kanban

- Horizontal board scroll  
- Column min-width ~260–280px  
- Drag optional on mobile — provide move menu  

---

## Charts

- Full width of widget  
- Rotate or abbreviate x labels  
- Hide minor series first  
- Touch tooltips on tap  

---

## Testing matrix (Phase 14)

| Width | Must pass |
|-------|-----------|
| 360 | Login, Home, list, detail, create, approve |
| 768 | Dashboard 2-col, drawer |
| 1280 | Full shell |
| 1920 | No absurd stretched forms |

---

## Anti-patterns

- Desktop-only side-by-side that overflows mobile without adaptation  
- Disabling features entirely on mobile without alternative  
- Tiny tap targets in sticky bars  
