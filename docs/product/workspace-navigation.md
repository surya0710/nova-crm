# Deliverable 5 — Workspace Navigation Experience

In-workspace navigation behavior. Complements [navigation-map.md](./navigation-map.md) and [sidebar-blueprint.md](./sidebar-blueprint.md) with interaction rules.

---

## Layers (recap)

| Layer | Where | Changes when |
|-------|-------|--------------|
| **Global chrome** | Top bar | Org, search, help, alerts, user |
| **Workspace switcher** | Sidebar top | Switches entire primary nav |
| **Primary navigation** | Sidebar | Workspace-specific modules |
| **Secondary navigation** | Page subnav / tabs | Module views |
| **Context navigation** | Module hubs | e.g. Recruitment sections |
| **Entity navigation** | Record page tabs | Related facets of one record |
| **Utilities** | Sidebar lower / chrome | Recents, Favorites, Pins |

---

## Primary navigation behavior

1. Click switches route; active item uses `aria-current="page"`.  
2. Nested groups (Revenue, People) accordion: one open by default (remember per user).  
3. Badge counts update without full reload when possible.  
4. Hidden if permission/plan denies — no locked rows.  
5. “More” holds extended items; opening More is sticky for session.

### Switching workspaces

- Primary nav replaces entirely.  
- Recents/Favorites remain but filter highlight by relevance.  
- Search default scope updates.  
- URL may prefix or query `workspace=` (implementation choice); deep links restore workspace.

---

## Secondary navigation

Used for alternate views of the same set:

| Module | Secondary |
|--------|-----------|
| Opportunities | Board (Pipeline) · List · Forecast |
| Tasks | Board · List · Timeline · Calendar |
| Projects index | List · Board (future) · Dashboard |
| Reports | Sales · Delivery · People · Finance |

Behavior:

- Persist last view per user+module.  
- View state (filters) persists when toggling secondary when possible.  
- Mobile: horizontal scroll tabs or select.

---

## Context navigation

Hubs with multiple sibling areas (not just views):

| Hub | Context items |
|-----|---------------|
| Recruitment | Dashboard · Openings · Candidates · Interviews · Offers · Careers |
| Leave | My · Team · Balances · Admin |
| Resources | Planner · Capacity · Timeline · Forecast |
| Configuration Hub | Group cards (not a second sidebar of 50 links) |

Context nav is **horizontal under page title** or left local nav on wide screens — never a third global sidebar.

---

## Entity navigation

On a record:

1. **Header** — identity, status, primary CTA, overflow  
2. **Tabs** — Overview · Activity · Files · Related…  
3. **Related chips** — cross-workspace links (“Open Customer”)  

Rules:

- Tab order: Overview first; Settings/Danger last.  
- Unsaved changes guard when leaving tab/page.  
- Deep link to tab via hash or `?tab=`.  
- Do not nest foreign module sidebars.

---

## Recent items

| Rule | Value |
|------|-------|
| Count | 8–10 |
| Types | Lead, Customer, Opportunity, Project, Task, Employee, Candidate, Invoice, … |
| Update | On visit of show page |
| Click | Navigate + switch workspace if needed |
| Clear | Per-item remove + Clear all |
| Empty | Hide section |

---

## Favorites

- User-pinned pages or entities  
- Max 5 in sidebar; overflow page  
- Available collapsed as star flyout  
- Favoriting an entity shows star on entity header  

---

## Pinned pages

- Role/org defaults (not personal)  
- Distinct iconography from Favorites  
- Admin-configurable later  

---

## Breadcrumbs

Pattern: `Workspace > Primary > Section > Record`

| Behavior | Spec |
|----------|------|
| Links | All but current |
| Truncation | Middle ellipsis; full in title tooltip |
| Back | Browser back preferred; soft “Back to list” on entity pages |
| Cross-workspace | Include workspace name when trail crosses |

Examples:

- `CRM > Opportunities > Acme Expansion`  
- `Projects > Projects > Website Redesign > Risks`  
- `HR > Recruitment > Candidates > Jane Doe`  

---

## Tabs

| Use | Avoid |
|-----|-------|
| Facets of one entity | Mimicking primary nav |
| View modes (board/list) | More than 7 without overflow |
| Settings sub-sections | Mixing ops + config without label |

Keyboard: arrows within tablist when focused (ARIA tabs pattern).

---

## Mobile navigation

1. Menu opens drawer with workspace selector.  
2. Primary accordion.  
3. Secondary becomes select or scroll chips.  
4. Entity tabs scroll horizontally.  
5. Breadcrumbs collapse to Back + current title.

---

## Consistency checklist

Every workspace must provide:

- [ ] Primary items ≤ 9 before More  
- [ ] Home landing reachable in one click  
- [ ] Recents + Favorites  
- [ ] Breadcrumbs on entity pages  
- [ ] Search scope indicator  

---

## Anti-patterns

- Different tab patterns per module without reason  
- Breadcrumbs that include filter query strings  
- Favorites that 403 after role change (prune on load)  
- Context nav that duplicates primary items  
