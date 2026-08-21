# Konnect Nex Product Blueprint — Overview

**Phases:** 13.1 Information Architecture · 13.2 Workspace Experience · 13.3 Enterprise Design System · 13.4 Frontend Implementation Standards  
**Status:** Authoritative product reference  
**Scope:** Documentation only — no UI or production code in these phases

---

## Purpose

| Phase | Answers |
|-------|---------|
| **13.1** | What is Konnect Nex? How is the product organized? |
| **13.2** | What does it feel like to work in Konnect Nex every day? |
| **13.3** | How does the product look and behave? → [../design/overview.md](../design/overview.md) |
| **13.4** | How do engineers build the UI? → [../frontend/overview.md](../frontend/overview.md) |

Together these blueprints are the source of truth for Phase 14+ UX, navigation, dashboard, and design implementation.

---

## Product principles

### Organization (13.1)

| Principle | Meaning |
|-----------|---------|
| Simple for first-time users | Default nav shows only what the role needs |
| Powerful for advanced users | Progressive disclosure, favorites, search, command palette |
| Minimize clicks | Primary work ≤ 2 clicks from workspace home |
| Progressive disclosure | Advanced items behind More, Hub, or entity context |
| Consistent navigation | Same patterns across workspaces |
| Clear ownership | Every screen has workspace, module, persona owner |
| Predictable modules | Shared list/detail/settings patterns |
| Role-aware experience | Menus, dashboards, search respect permissions |

### Daily experience (13.2)

| Principle | Meaning |
|-----------|---------|
| Immediate clarity | Know where you are and what needs attention |
| Role-focused | Homes and actions match persona |
| Minimal cognitive load | ≤ 9 primary nav items; widget limits |
| Fast daily work | Quick Actions + Attention rail |
| Consistent layouts | Shared home anatomy and widget standards |
| Predictable actions | One label, one place for each intent |
| Personalized | Layouts, favorites, landing — within security |
| Progressive disclosure | Power features after basics |

A user should immediately know: **where they are**, **what needs attention**, **what to do next**, **how to go elsewhere**.

---

## Document map

### Phase 13.1 — Information Architecture

| Document | Deliverable |
|----------|-------------|
| [module-audit.md](./module-audit.md) | Product audit of every module |
| [workspaces.md](./workspaces.md) | Workspace architecture |
| [module-classification.md](./module-classification.md) | Module categories |
| [screen-inventory.md](./screen-inventory.md) | Screen inventory |
| [navigation-map.md](./navigation-map.md) | Navigation hierarchy |
| [sidebar-blueprint.md](./sidebar-blueprint.md) | Enterprise sidebar design |
| [settings-architecture.md](./settings-architecture.md) | Configuration Hub |
| [configuration-registry.md](./configuration-registry.md) | How to add modules to the hub catalog |
| [search-architecture.md](./search-architecture.md) | Global search behavior |
| [dashboard-ownership.md](./dashboard-ownership.md) | Dashboard ownership |
| [user-personas.md](./user-personas.md) | User personas |
| [business-flows.md](./business-flows.md) | Primary business journeys |
| [navigation-review.md](./navigation-review.md) | Nav pain points |
| [product-glossary.md](./product-glossary.md) | Terminology |
| [phase14-backlog.md](./phase14-backlog.md) | Implementation backlog |

### Phase 13.2 — Workspace Experience

| Document | Deliverable |
|----------|-------------|
| [workspace-home-blueprints.md](./workspace-home-blueprints.md) | Landing page per workspace |
| [dashboard-blueprint.md](./dashboard-blueprint.md) | Dashboard hierarchy & philosophy |
| [widget-standards.md](./widget-standards.md) | Widget sizes, types, states |
| [quick-actions.md](./quick-actions.md) | Universal Quick Actions |
| [workspace-navigation.md](./workspace-navigation.md) | In-workspace nav behavior |
| [personalization.md](./personalization.md) | Favorites, layouts, prefs |
| [activity-experience.md](./activity-experience.md) | Activity feeds |
| [workspace-search.md](./workspace-search.md) | Per-workspace search |
| [workspace-notifications.md](./workspace-notifications.md) | Notification experience |
| [cross-workspace-experience.md](./cross-workspace-experience.md) | Cross-workspace transitions |
| [empty-states.md](./empty-states.md) | Empty & first-time UX |
| [accessibility.md](./accessibility.md) | A11y requirements |
| [dashboard-metrics.md](./dashboard-metrics.md) | Success metrics |

### Phase 13.3 — Design System

See [../design/overview.md](../design/overview.md) for the full design document map (tokens, components, templates, checklist).

### Phase 13.4 — Frontend Implementation

See [../frontend/overview.md](../frontend/overview.md) for Blade/Alpine/Tailwind standards, testing, review, and migration waves.

### Phase reports

| Report | Path |
|--------|------|
| 13.1 | [P13_PHASE_13_1_PROGRESS.md](../P13_PHASE_13_1_PROGRESS.md) |
| 13.2 | [P13_PHASE_13_2_PROGRESS.md](../P13_PHASE_13_2_PROGRESS.md) |
| 13.3 | [P13_PHASE_13_3_PROGRESS.md](../P13_PHASE_13_3_PROGRESS.md) |
| 13.4 | [P13_PHASE_13_4_PROGRESS.md](../P13_PHASE_13_4_PROGRESS.md) |

---

## Target product hierarchy

```
Konnect Nex (Organization tenant)
├── Workspaces
│   ├── Home (personal command center)
│   ├── CRM
│   ├── Projects
│   ├── HR (+ My HR mode)
│   ├── Marketing
│   ├── Operations
│   ├── Analytics
│   └── Administration
├── Cross-cutting
│   ├── Global Search / Command palette
│   ├── Notifications
│   ├── Activity
│   ├── Knowledge Center
│   └── Configuration Hub
└── External surfaces
    ├── Careers portal
    └── Platform console
```

Each workspace home shares: **Attention · KPIs · Widgets · Quick Actions · Activity · Pins**.

---

## How to use this blueprint

1. **Product / design** — IA from 13.1; interaction from 13.2; terms from glossary.  
2. **Engineering (Phase 14+)** — [phase14-backlog.md](./phase14-backlog.md); validate with [dashboard-metrics.md](./dashboard-metrics.md).  
3. **New modules** — Classify, assign workspace, define home widgets/actions, update inventory.

---

## Out of scope (Phases 13.1–13.2)

- Implementing sidebar, routes, Blade, or controllers  
- Changing RBAC seeders or permissions  
- Renaming database tables or API resources  
- Building Finance/Support/Assets beyond documented placeholders  

---

## Phase outcome

Konnect Nex has:

- **Information Architecture** — how the product is organized  
- **Workspace Experience** — how users interact with it daily  
- **Design System** — how the product looks and behaves  
- **Frontend Implementation Standards** — how engineers build it  

These documents are the authoritative reference for the Phase 14 UX redesign.
