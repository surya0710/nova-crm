# Deliverable 8 — Search Architecture

Defines Global Search behavior for NovaCRM. Current implementation: `SearchController` + `SearchService` (+ metadata search context).

---

## Purpose

Help users find **records and destinations** in ≤ 2 seconds without knowing which module owns them.

---

## Entry points

| Entry | Behavior |
|-------|----------|
| Header search field | Submits to `/search?q=` (current) |
| Keyboard shortcut | `Ctrl+K` / `⌘K` opens command palette (Phase 14) |
| Command palette modes | Search entities · Jump to page · Run action |
| Workspace-scoped toggle | “This workspace” vs “Everywhere” |

---

## Search categories

| Category | Entities | Workspace affinity |
|----------|----------|--------------------|
| **CRM** | Leads, Customers, Opportunities, Products, Quotations, Invoices, Payments | CRM |
| **Projects** | Projects, Tasks, Portfolios, Programs, Risks, Issues, Budgets, Baselines, Progress updates, Project reports, Labels, Templates, Mentions, Task comments, Resource allocations | Projects |
| **People** | Employees, Directory profiles, Candidates, Users (admin) | HR / Admin |
| **HR operations** | Leave applications, Job openings, Offers | HR |
| **Knowledge** | Documentation articles | Knowledge |
| **Navigation** | Sidebar destinations, Settings sections | All |
| **Actions** | Create Lead, New Task, … (palette only) | All |

**Gap today:** SearchService is strong on CRM + Projects; People/HR/Knowledge/Navigation need extension.

---

## Result presentation

```
Query: "acme"
├── Customers (3)
│   └── Acme Corp — Customer
├── Opportunities (1)
│   └── Acme Expansion — Opportunity · Stage Proposal
├── Invoices (2)
├── Projects (1)
└── Employees (1)
```

Rules:

- Group by category; max N per group (default 5) with “See all”.
- Show entity type, title, subtitle (status/owner/date), workspace icon.
- Empty categories omitted.
- Zero results: suggest navigation destinations + create actions if permitted.

---

## Recent searches

- Store last 8 queries per user+org (client + optional server).
- Show when focus with empty query.
- Click re-runs search.
- Clear all control.

---

## Pinned searches

- User pins a query or saved filter shortcut.
- Display under Recents as “Pinned”.
- Distinct from entity Favorites in sidebar.

---

## Saved searches

| Concept | Role |
|---------|------|
| **Saved Filter** (existing) | Module list filters (`saved-filters`) |
| **Saved Search** | Global query + category facets |

Phase 14: unify UX — “Save this search” from global results can create a Saved Filter when scoped to one module, or a global Saved Search otherwise.

---

## Context-aware results

| Context | Boost |
|---------|-------|
| Active workspace CRM | CRM entities ranked higher |
| On Customer page | Related opportunities/invoices/projects boosted |
| On Project page | Tasks, risks, members boosted |
| HR workspace | Employees, candidates boosted |
| Typing `@` in editors | Mention search (existing mentions) |

Context never **hides** permitted global matches unless user selects “This workspace”.

---

## Keyboard shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl/⌘ + K` | Open palette |
| `Ctrl/⌘ + /` | Focus header search (optional) |
| `↑ ↓` | Move selection |
| `Enter` | Open selected |
| `Esc` | Close |
| `Ctrl/⌘ + Enter` | Open in new tab (future) |

---

## Entity ranking

Score components (target):

1. **Exact match** on code/number (invoice #, project code)  
2. **Prefix match** on name  
3. **Fuzzy / contains** match  
4. **Recency** of record update or user interaction  
5. **Workspace boost**  
6. **Permission eligibility** (hard filter, not score)  
7. **Metadata field matches** (via MetadataSearchService)

Stable tie-break: entity type priority then updated_at desc.

Suggested type priority: Customer → Opportunity → Lead → Project → Employee → Invoice → Task → Other.

---

## Permission filtering

- Every result must pass the same view permission as the destination screen.
- Soft-deleted/archived: include only if user can view archived.
- Cross-org leakage forbidden; TenantContext always applied.
- Field-level metadata: respect metadata field permissions.

---

## Cross-workspace search

| Mode | Scope |
|------|-------|
| **Everywhere** (default from Home / palette) | All permitted categories |
| **This workspace** | Category set for active workspace |
| **Module** | From list page search boxes (local) |

Cross-workspace results show a workspace badge; selecting switches workspace if needed then navigates.

---

## Knowledge search

Keep `knowledge.search` for docs; also federate top 3 doc hits into Global Search under Knowledge category (`config/documentation.php`).

---

## Non-goals (Phase 14)

- Full Elasticsearch requirement (may remain DB `LIKE`/scout later)
- Searching binary file contents
- Searching other tenants

---

## Acceptance for later implementation

- [ ] Categories UI on results page  
- [ ] HR People entities included  
- [ ] Keyboard palette  
- [ ] Recent + pinned queries  
- [ ] Workspace scope toggle  
- [ ] Permission-safe ranking  
- [ ] Knowledge federation  
