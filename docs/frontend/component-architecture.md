# Deliverable 5 — Frontend Component Architecture

Engineering organization of UI components mapped to the design library ([../design/component-library.md](../design/component-library.md)).

---

## Layers

```
Primitives (ui/, forms/)
    → Patterns (tables/, filters/, nav/, feedback/)
        → Composites (widgets/, charts/, kanban/, domain/)
            → Pages (module views / workspaces)
```

Dependencies only point **downward**. Pages may use any layer; primitives never import domain.

---

## Catalog → folder map

| Component family | Target path | Notes |
|------------------|-------------|-------|
| Buttons | `components/ui/button` | Unify primary/secondary/danger |
| Cards | `components/ui/card` | |
| Badges | `components/ui/badge` | Semantic variants |
| Avatars | `components/ui/avatar` | |
| Alerts / flash | `components/feedback/*` | Keep `flash-messages` |
| Modal / drawer / dropdown / tooltip / tabs | `components/ui/*` | Evolve `modal`, `dropdown` |
| Inputs / labels / errors | `components/forms/*` | Evolve Breeze inputs |
| Field wrapper | `components/forms/field` | Label+control+help+error |
| Tables | `components/tables/*` | Shell, header, pagination |
| Filters | `components/filters/*` | Bar, chips, saved |
| Navigation / sidebar | `components/nav/*` | `sidebar-link`, switcher, breadcrumbs, palette |
| Workspace shell bits | `components/nav` + `layouts` | |
| Dashboard widgets | `components/widgets/*` | Frame + skeletons |
| Charts | `components/charts/*` | Wrapper only; lib optional |
| Kanban / timeline | `components/kanban`, `timeline` | |
| Comments / activity | `components/comments`, `activity` | |
| Attachments | `components/attachments` | Evolve `attachments-panel` |
| Domain modals/panels | `components/domain/*` | lead-convert, opportunity-close, tasks-panel, … |

---

## API consistency

Every primitive supports where applicable:

- `variant`, `size`  
- `$attributes` merge  
- Disabled / loading  
- Slot for icon  

Document props in a short Blade comment block at file top until a living styleguide exists.

---

## Widget components

| Piece | Responsibility |
|-------|----------------|
| `widgets.frame` | Title, overflow, refresh, footer |
| `widgets.skeleton` | Loading |
| `widgets.kpi` | KPI body |
| Feature widgets | Domain-specific bodies fed by controller/API |

Data loading: prefer server-rendered first paint; Alpine/axios refresh optional.

---

## Chart components

- Blade wrapper for title/legend/empty  
- Chart library (if any) loaded only on pages that need it  
- Provide `<table class="sr-only">` or expandable data table  

---

## Registration

- Anonymous components: auto-discovered from `resources/views/components`  
- Class components: `app/View/Components` for layouts and heavy logic  
- Alpine.data: register in `app.js`  

---

## Deprecation path

1. Add new `ui.button`  
2. Make old `primary-button` a thin alias  
3. Codemod / gradual replace  
4. Remove alias after grace period  

---

## Anti-patterns

- Domain component re-implementing button styles inline  
- Circular includes between domain components  
- “Utils” Blade that dumps 20 unrelated macros  
