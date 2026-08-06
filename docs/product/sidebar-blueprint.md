# Deliverable 6 — Sidebar Blueprint

Enterprise sidebar for Konnect Nex. Complements [navigation-map.md](./navigation-map.md).

---

## Goals

- Role-aware without feeling empty or noisy
- ≤ 9 primary items visible for typical roles
- Progressive disclosure for power features
- Works collapsed (icons) and on mobile (drawer)

---

## Layout (desktop expanded)

```
┌────────────────────────────┐
│ Org logo + name            │
│ Org switcher (if multi)    │
├────────────────────────────┤
│ Workspace selector         │  ← segmented or select
├────────────────────────────┤
│ ★ Favorites (optional)     │
│ 📌 Pinned (role defaults)  │
├────────────────────────────┤
│ PRIMARY NAV                │
│  · item                    │
│  · item with children      │
│  · More ▾                  │
├────────────────────────────┤
│ ⏱ Recents (collapsible)    │
├────────────────────────────┤
│ Administration (if admin)  │  ← footer zone, not mixed with ops
│  Configuration             │
├────────────────────────────┤
│ User chip + logout         │
└────────────────────────────┘
```

Width: **16rem** expanded · **4rem** collapsed.

---

## Workspace selector

| Aspect | Spec |
|--------|------|
| Control | Compact select or icon rail at top of sidebar |
| Options | Only workspaces with ≥1 permitted module |
| Persistence | Remember last workspace per user+org |
| Keyboard | Part of command palette (`go to workspace`) |
| Visual | Active workspace name always visible |

Default order: Home · CRM · Projects · HR · Marketing · Operations · Analytics · Administration.

---

## Primary navigation

Rules:

1. Items gated by permission **and** module enablement/plan.
2. Nested groups (Revenue, People) expand in-place; only one nested group open by default.
3. Active state matches route prefixes (preserve current `routeIs` patterns).
4. Labels use [product-glossary.md](./product-glossary.md).
5. Badge counts (optional): Approvals, overdue tasks — max one badge type per item.

### Progressive disclosure

| Tier | Who sees it | Examples |
|------|-------------|----------|
| Core | Default for role | Leads, Projects, Leave |
| Extended | “More” or expand | Products, Announcements, Templates |
| Admin | Administration / Config | RBAC, Metadata, Workflows |

First-time users (employee, sales executive) never see Extended until they open More or an admin pins it.

---

## Favorites

- User-controlled pin of any navigable page or entity
- Stored per user+organization
- Max display 5; overflow “All favorites”
- Available in collapsed mode as star icon tray

---

## Pinned pages

- Role or org defaults (e.g. Recruiter → Candidates)
- Distinct from Favorites (system vs personal)
- Editable later via Administration (Phase 14+)

---

## Recent items

- Last 8–10 unique entities/pages
- Types: Lead, Customer, Opportunity, Project, Employee, Candidate, Invoice, Task
- Clear on demand
- Hidden if empty

---

## Administration block

Separated visually (border/top spacing):

- Users  
- Roles  
- Configuration Hub  
- Billing (owners)

Knowledge Center moves to **header Help**, not sidebar Settings.

Profile moves to **user menu**, not sidebar.

---

## Collapsed mode

| Element | Behavior |
|---------|----------|
| Org | Logo only; tooltip name |
| Workspace | Icon; tooltip |
| Primary items | Icons + tooltips |
| Nested | Flyout menus on hover/click |
| Favorites/Recents | Icon buttons with flyouts |
| User | Avatar only |

Do not truncate labels mid-word in tooltips.

---

## Mobile mode

| Aspect | Spec |
|--------|------|
| Pattern | Off-canvas drawer from menu button |
| Workspace | Full-width selector at top |
| Primary nav | Accordion groups |
| Favorites/Recents | Above primary |
| Admin | Bottom accordion |
| Dismiss | Overlay tap, route change, Escape |

Touch targets ≥ 44px.

---

## Role-aware menus (examples)

### Sales Executive
- Workspaces: Home, CRM  
- CRM: Leads, Customers, Opportunities, Revenue  
- Hide: Products (unless permitted), Admin, HR

### Project Manager
- Home, Projects, Operations (Tasks)  
- Projects: full primary set  
- Resources visible

### HR Manager
- Home, HR, Analytics (people)  
- HR: People, Time, Leave, Recruitment, Performance, Payroll  
- ESS hidden if not needed

### Employee (ESS only)
- Home, HR (My HR only)  
- No Administration, no CRM

### Organization Administrator
- All permitted workspaces  
- Administration block visible  
- Configuration Hub highlighted

### Recruiter
- Home, HR  
- HR primary emphasis on Recruitment children  
- Pin Candidates

---

## Avoiding overwhelm

1. Cap visible primary links at **9**.  
2. Collapse Self-Service into HR My HR.  
3. Remove Knowledge/Profile from sidebar.  
4. Do not list every HR leaf at once — use groups.  
5. Hide empty future workspaces.  
6. Prefer search/command palette over mega-menus.

---

## Current → target mapping

| Current (`sidebar.blade.php`) | Target |
|-------------------------------|--------|
| Flat CRM including Tasks/Projects/Resources | Split across CRM / Operations / Projects |
| Flat HR 15+ links | Grouped HR + More |
| Self-Service section | HR ESS mode |
| Settings: Integrations, Workflows, Metadata, RBAC, Team, API, Knowledge, Profile | Admin block + Config Hub + chrome Help/User |
| Org header block | Keep; add workspace selector beneath |

---

## Accessibility & i18n

- Landmark `<nav aria-label="Workspace">`
- `aria-current="page"` on active link
- Collapsed tooltips = accessible names
- All labels via `__()` / glossary terms (`crm_term` where applicable)
