# Deliverable 12 — Navigation Pain Points Review

Review of the current product navigation (primarily `resources/views/layouts/sidebar.blade.php` and settings hub) with proposed solutions.

---

## 1. Duplicate menus

| Issue | Evidence | Proposal |
|-------|----------|----------|
| Settings surfaces duplicated | Organization Settings hub links Access Control, Integrations, API; same items also in sidebar | Sidebar keeps Administration only; details in Configuration Hub |
| HR structure dual homes | Hub redirects to `/hrms/branches` etc. while operational HR also implies structure | Structure = Configuration only; ops nav links “Manage structure” |
| Dashboards compete | Main Dashboard + HR + Manager + Leave + Recruitment + Projects dashboards | One Home; others under workspace secondary nav |
| Profile vs ESS Profile | Sidebar Profile + ESS Profile | User menu Profile; ESS Profile under My HR |

---

## 2. Hidden functionality

| Hidden capability | Route area | Proposal |
|-------------------|------------|----------|
| Portfolios / Programs | `portfolios.*`, `programs.*` | Projects primary nav |
| Risks / Issues global | `risks.*`, `issues.*` | Projects → Risks & Issues |
| Resource subviews | capacity, timeline, forecast | Under Resources |
| Assignment rules | `assignments.*` | CRM Configuration |
| Lead import | `leads.import.*` | CRM More / list toolbar |
| Project catalogs & templates | `project-*`, templates | Configuration → Projects |
| Notification preferences | `notification-preferences.*` | User menu |
| Marketing providers | `marketing.providers.*` | Marketing workspace + Integrations |
| Executive project/portfolio views | executive routes | Analytics or Projects → Executive |

---

## 3. Deep navigation

| Issue | Impact | Proposal |
|-------|--------|----------|
| Recruitment depth behind one link | Extra clicks; discoverability | Secondary nav for Recruitment |
| Project show has many nested tools | Users miss Gantt/budget/risk | Context tabs + “More” |
| Metadata complexity | Admins lost | Hub section with guided IA |
| Careers settings inside recruitment nest | Hard to find | Hub card + Recruitment settings tab |

---

## 4. Confusing terminology

| Current | Problem | Official term |
|---------|---------|---------------|
| Pipeline (nav) vs Opportunity (entity) | Mixed language | Nav: Opportunities; view: Pipeline board |
| Metadata Fields | Developer jargon | Custom Fields |
| Team (users) vs Teams (HR) | Collision | Users vs Teams |
| Self-Service vs HR Attendance | Duplicate words | My Attendance under My HR |
| Resource Planner alone | Unclear family | Resources |
| Finance under Analytics vs Invoices under CRM | Unclear ownership | Revenue ops in CRM; Finance reports in Analytics |
| Organization Settings vs Settings section | Vague | Configuration Hub |

See [product-glossary.md](./product-glossary.md).

---

## 5. Inconsistent actions

| Issue | Proposal |
|-------|----------|
| Create actions sometimes only on index, sometimes header | Standard page header: Primary CTA + overflow |
| Convert lead / create invoice paths vary | Entity “Next steps” pattern |
| Archive/delete placement differs | Always in overflow ⋯ |
| Import only on leads | Pattern for customers/products where supported |

---

## 6. Repeated settings

| Repeated concern | Locations | Consolidation |
|------------------|-----------|---------------|
| Notifications | Org settings, user prefs, project prefs | Org defaults in Hub; personal in User menu; project overrides on project |
| Access | Sidebar RBAC + Hub card | Single Administration → Roles |
| Integrations | Sidebar + Hub | Hub Integrations group |

---

## 7. Unused or low-traffic screens

| Screen type | Notes | Proposal |
|-------------|-------|----------|
| Future Assets | Hidden in config; routes retained | Keep hidden until GA |
| Support/AI permission groups | No UI module | Hide empty nav/groups |
| Optional Dashboard card in settings | Not a setting | Remove from settings catalog |
| Some portfolio report routes | Power-user | Under Analytics/Projects Reports, not primary |

Do not delete routes in Phase 13.1; mark IA priority P2/P3.

---

## 8. Complex workflows in UI

| Workflow | Complexity driver | Proposal |
|----------|-------------------|----------|
| Hire candidate → employee | Multi-module | Guided handoff wizard |
| Quote → invoice → payment | Scattered nav | Revenue group + next-step CTAs |
| Leave approval | Many sibling links | Leave hub |
| Resource planning | Multiple URLs | Resources section with views |
| Custom fields + layouts | Expert IA | Progressive disclosure in Custom Fields |

---

## 9. Structural IA issues

1. **Projects suite nested under CRM** — highest severity.  
2. **Tasks under CRM** — cross-cutting; move to Operations/Home.  
3. **HR flat list** — cognitive overload.  
4. **No workspace concept** — one menu for all personas.  
5. **Knowledge under Settings** — wrong category.  
6. **Marketing absent** — capability without home.

---

## Prioritized remediation (feeds Phase 14)

| Priority | Pain | Solution doc |
|----------|------|--------------|
| P0 | Projects under CRM | workspaces + sidebar |
| P0 | HR overload | grouped HR nav |
| P0 | Settings duplicates | settings-architecture |
| P1 | Hidden portfolios/resources | navigation-map |
| P1 | Terminology | product-glossary |
| P1 | Search gaps | search-architecture |
| P2 | Marketing workspace | workspaces |
| P2 | Command palette | phase14-backlog |
| P2 | Dashboard competition | dashboard-ownership |

---

## Success metrics (later)

- Median clicks to core entity ≤ 2 from workspace home  
- New hire (Employee persona) sees ≤ 8 nav items  
- Support tickets about “where is X” decline  
- Admin configures leave policy without leaving Hub  
