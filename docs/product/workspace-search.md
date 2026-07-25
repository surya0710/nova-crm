# Deliverable 8 — Workspace Search

Per-workspace search behavior extending [search-architecture.md](./search-architecture.md).

---

## Modes

| Mode | Trigger | Scope |
|------|---------|-------|
| **Workspace default** | Search while in a workspace | That workspace’s entity set |
| **Everywhere** | Toggle or Home | All permitted |
| **Context** | Search from entity/list page | Current module + related |
| **Advanced** | “Advanced search” link | Field-level filters |
| **Palette** | `Ctrl/⌘K` | Entities + pages + actions |

Chrome always shows current scope chip: `CRM` · `Everywhere`.

---

## Per-workspace defaults

### Home

| Field | Spec |
|-------|------|
| **Default entities** | All permitted (Everywhere) |
| **Suggested** | My open tasks, recent customers/projects |
| **Popular** | Org-wide top queries (admin opt-in later) |

### CRM

| Field | Spec |
|-------|------|
| **Default entities** | Leads, Customers, Opportunities, Products, Quotations, Invoices, Payments |
| **Suggested** | My open leads, overdue invoices |
| **Popular** | Customer names, invoice numbers |

### Projects

| Field | Spec |
|-------|------|
| **Default entities** | Projects, Tasks, Portfolios, Programs, Risks, Issues, Budgets, Mentions |
| **Suggested** | My projects, watched, at-risk |
| **Popular** | Project codes, task titles |

### HR

| Field | Spec |
|-------|------|
| **Default entities** | Employees, Candidates, Job openings, Leave applications (when indexed) |
| **Suggested** | Directory, my leave, open openings |
| **Popular** | Employee name, candidate name |
| **Gap** | Must be implemented in SearchService (Phase 14 P0) |

### Marketing

| Field | Spec |
|-------|------|
| **Default entities** | Leads (with source), Providers, Campaigns (future) |
| **Suggested** | Disconnected providers, top sources |

### Operations

| Field | Spec |
|-------|------|
| **Default entities** | Tasks, Assignment rules/pools (admin) |
| **Suggested** | Overdue tasks, my tasks |

### Analytics

| Field | Spec |
|-------|------|
| **Default entities** | Report definitions + deep links into entities |
| **Suggested** | Finance, pipeline report, audit |

### Administration

| Field | Spec |
|-------|------|
| **Default entities** | Users, Roles, Settings sections, Integrations |
| **Suggested** | Invite, roles, configuration |

### Future (Finance / Support / Assets)

Default to their operational entities when modules ship.

---

## Recent searches

- Last 8 queries **per workspace scope** + global list  
- Show on focus when query empty  
- Include scope badge  

---

## Saved searches

| Kind | Scope | Storage concept |
|------|-------|-----------------|
| Saved Filter | Module list | Existing `saved-filters` |
| Saved Search | Global/workspace query + facets | New (Phase 14) |

From results: **Save search** → if single category, offer Saved Filter; else Saved Search.

---

## Suggested searches

Computed:

1. Role defaults (e.g. Recruiter → “candidates interview”)  
2. User recents  
3. Needs-attention shortcuts (“overdue invoices”)  

Displayed as chips under empty search.

---

## Popular searches

- Optional org-level aggregates (privacy: no personal query leakage)  
- Admin enablement  
- Fallback: curated popular per workspace  

---

## Context search

| Context | Boost / filter |
|---------|----------------|
| On Customer | Opportunities, Invoices, Projects for customer |
| On Project | Tasks, Risks, Issues, Members |
| On Employee | Leave, Attendance, Documents |
| List page search box | Local module only (does not replace global) |

---

## Advanced search

Capabilities:

- Entity type multi-select  
- Status, owner, date range  
- Custom fields (metadata) where searchable  
- Saved as Saved Search  

Entry: results page → Advanced · or palette `advanced search`.

---

## Permission filtering

Unchanged: no result without view permission; tenant scoped always.

---

## Empty results

Per [empty-states.md](./empty-states.md): clear message, scope toggle hint, create action if allowed, Knowledge link.

---

## Implementation notes

- Reuse `SearchService` + category UI  
- Workspace scope = filter entity allow-list  
- Do not fork a separate search stack per workspace  
