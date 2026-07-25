# Deliverable 14 — Phase 14 Implementation Backlog

Prioritized backlog to implement the Phase 13.1 IA blueprint. **No production code in 13.1.**

Priority: **P0** critical · **P1** high value · **P2** important · **P3** foundation/later  
Effort: **S** · **M** · **L** · **XL**

---

## Theme A — Critical UX / navigation

| ID | Item | Priority | Effort | Depends on | Outcome |
|----|------|----------|--------|------------|---------|
| A1 | Introduce workspace switcher + persistence | P0 | L | IA docs | Users land in role-default workspace |
| A2 | Split sidebar: remove Projects/Tasks/Resources from CRM group | P0 | M | A1 | CRM nav only CRM |
| A3 | Build Projects workspace primary nav (Projects, Portfolios, Programs, Resources, Risks & Issues, Reports) | P0 | L | A1 | Hidden project suite discoverable |
| A4 | Regroup HR into People / Time / Leave / Recruitment / Performance / Payroll / More | P0 | M | A1 | Shorter HR cognitive load |
| A5 | ESS as My HR mode (hide admin HR when only ess.access) | P0 | M | A4 | Employees unconfused |
| A6 | Move Knowledge + Profile out of Settings sidebar | P1 | S | — | Cleaner admin nav |
| A7 | Administration block + remove duplicate Integrations/Workflows/Metadata/API from primary settings list | P0 | M | C1 | Single admin path |

---

## Theme B — Workspace implementation

| ID | Item | Priority | Effort | Outcome |
|----|------|----------|--------|---------|
| B1 | Home workspace shell (recents, my work) | P0 | M | Clear landing |
| B2 | CRM workspace IA (Revenue group, Catalog/More) | P0 | M | Predictable sales nav |
| B3 | Operations workspace or Home My Work for Tasks | P1 | M | Tasks correctly homed |
| B4 | Analytics workspace nav wrapping Reports/Finance/Audit | P1 | M | Insight home |
| B5 | Marketing workspace MVP (Attribution + Providers UI) | P2 | M | Marketing has a home |
| B6 | Hide empty future workspaces (Finance/Support/Assets) | P0 | S | No dead ends |
| B7 | Persona default landing map | P1 | S | Role-aware first paint |

---

## Theme C — Settings consolidation

| ID | Item | Priority | Effort | Outcome |
|----|------|----------|--------|---------|
| C1 | Expand `organization_settings.php` to Hub groups A–K | P0 | L | Configuration Hub complete |
| C2 | Add Hub cards: Workflows, Custom Fields, Assignments, Project catalogs | P0 | M | Orphans findable |
| C3 | Remove Dashboard card from settings catalog | P1 | S | Hub purity |
| C4 | Deep-link module “Settings” buttons to Hub sections | P1 | M | No duplicate pages |
| C5 | Rename Metadata → Custom Fields in UI | P1 | S | Glossary compliance |
| C6 | Rename Team → Users in admin nav | P1 | S | Glossary compliance |

---

## Theme D — Sidebar redesign

| ID | Item | Priority | Effort | Outcome |
|----|------|----------|--------|---------|
| D1 | Implement sidebar blueprint layout (favorites, pinned, recents) | P1 | L | Enterprise sidebar |
| D2 | Collapsed icon mode + tooltips | P1 | M | Density for power users |
| D3 | Mobile drawer accordion | P1 | M | Mobile usable |
| D4 | Badge counts for approvals/tasks | P2 | M | Actionability |
| D5 | Apply glossary labels across sidebar | P1 | S | Consistent language |

---

## Theme E — Dashboard redesign

| ID | Item | Priority | Effort | Outcome |
|----|------|----------|--------|---------|
| E1 | Role-default Home layouts | P1 | L | Less empty/noisy Home |
| E2 | Demote secondary dashboards from main sidebar; link from workspace | P0 | M | One Home |
| E3 | CRM workspace summary dashboard | P2 | M | Sales home |
| E4 | Analytics overview composing existing reports | P2 | L | Executive path |
| E5 | Align widget `module` tags to workspaces | P2 | M | Plan/workspace filtering |

---

## Theme F — Search improvements

| ID | Item | Priority | Effort | Outcome |
|----|------|----------|--------|---------|
| F1 | Categorized results UI | P1 | M | Scannable search |
| F2 | Add Employees + Candidates (+ leave/openings) to SearchService | P0 | L | HR findability |
| F3 | Workspace scope toggle | P1 | M | Less noise |
| F4 | Recent + pinned queries | P2 | M | Speed |
| F5 | Federate Knowledge hits | P2 | M | Help in search |
| F6 | Ranking pass (exact code, recency, workspace boost) | P2 | M | Better top hit |

---

## Theme G — Command palette foundation

| ID | Item | Priority | Effort | Outcome |
|----|------|----------|--------|---------|
| G1 | `Ctrl/⌘K` palette shell | P1 | M | Power-user nav |
| G2 | Jump-to-page index from nav map | P1 | M | Fast navigation |
| G3 | Entity search mode reusing SearchService | P1 | M | Unified find |
| G4 | Create actions (Lead, Task, Leave) | P2 | M | Minimize clicks |
| G5 | Workspace switch commands | P2 | S | Keyboard IA |

---

## Theme H — Flow & terminology polish

| ID | Item | Priority | Effort | Outcome |
|----|------|----------|--------|---------|
| H1 | Opportunities nav label + Pipeline as view tab | P1 | S | Glossary |
| H2 | Customer 360 related tabs (deals, invoices, projects) | P1 | L | Revenue flow |
| H3 | Recruitment secondary nav | P1 | M | Hiring flow |
| H4 | Leave hub (My / Team / Admin) | P1 | L | Leave flow |
| H5 | Guided Candidate → Employee handoff | P2 | L | Talent flow |
| H6 | Opportunity next-step CTAs (Quote / Project) | P2 | M | Revenue flow |

---

## Suggested Phase 14 sequencing

```
Sprint 1 (P0 nav skeleton)
  A1 → A2 → A3 → A4 → A5 → A7 → B6 → E2

Sprint 2 (Config + CRM/HR polish)
  C1 → C2 → C5 → C6 → B2 → H1 → H3

Sprint 3 (Sidebar + Search)
  D1 → D5 → F2 → F1 → F3 → G1 → G2 → G3

Sprint 4 (Dashboards + flows)
  E1 → B1 → B4 → H2 → H4 → F4 → G4
```

---

## Explicit non-goals for early Phase 14

- Rewriting RBAC data model  
- Building Finance/Support/Assets workspaces beyond placeholders  
- Platform console redesign  
- Breaking existing route names without redirects  

---

## Traceability

| Blueprint doc | Backlog themes |
|---------------|----------------|
| workspaces.md | A, B |
| navigation-map.md / sidebar-blueprint.md | A, D |
| settings-architecture.md | C |
| search-architecture.md | F, G |
| dashboard-ownership.md | E |
| product-glossary.md / navigation-review.md | H, A |
| business-flows.md / user-personas.md | H, B7, E1 |

---

## Definition of done (Phase 14 epic)

- [ ] Every audited module has a workspace home or Hub home  
- [ ] Sidebar matches blueprint for Sales, PM, HR, Employee, Admin personas  
- [ ] No duplicate settings entries in sidebar  
- [ ] Portfolios/Programs/Resources reachable without URL memorization  
- [ ] Search returns People entities  
- [ ] Command palette navigates primary destinations  
- [ ] Glossary terms applied to primary nav labels  
