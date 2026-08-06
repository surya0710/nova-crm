# Deliverable 1 — Product Module Audit

Audit of modules available in NovaCRM as of Phase 13.1. Based on tenant sidebar, routes (`routes/web.php`, `platform.php`, `careers.php`), settings catalog, and RBAC groups.

Complexity scale: **Low** | **Medium** | **High** | **Very High**

---

## 1. Dashboard (Workspace Home)

| Field | Detail |
|-------|--------|
| **Purpose** | Personalized landing surface with widgets, quick actions, recent activity |
| **Primary users** | All authenticated org members |
| **Related modules** | CRM, HRMS, Recruitment, Projects, Finance widgets, Notifications |
| **Current complexity** | High (widget registry, plan gates, preferences API) |
| **Navigation pain points** | Single “Dashboard” competes with HR/Project/Recruitment dashboards; unclear which is “home” |
| **Simplification opportunities** | Make this **Home workspace**; role-default layouts; link out to workspace dashboards |

---

## 2. CRM — Leads

| Field | Detail |
|-------|--------|
| **Purpose** | Capture and qualify inbound/outbound sales prospects |
| **Primary users** | Sales executive, Sales manager |
| **Related modules** | Customers, Pipeline, Assignments, Imports, Metadata, Workflows |
| **Current complexity** | Medium |
| **Navigation pain points** | Import and assignment rules not in CRM nav |
| **Simplification opportunities** | Lead actions (import, convert, assign) as list toolbar + settings under CRM config |

---

## 3. CRM — Customers

| Field | Detail |
|-------|--------|
| **Purpose** | Account/customer master records and statements |
| **Primary users** | Sales, Finance, Support (future) |
| **Related modules** | Leads, Pipeline, Quotations, Invoices, Payments |
| **Current complexity** | Medium |
| **Navigation pain points** | Statement export buried; relationship to Opportunities unclear in nav |
| **Simplification opportunities** | Customer 360 hub: deals, invoices, projects on one entity page |

---

## 4. CRM — Pipeline (Opportunities)

| Field | Detail |
|-------|--------|
| **Purpose** | Manage deals through sales stages |
| **Primary users** | Sales executive, Sales manager |
| **Related modules** | Leads, Customers, Quotations, Products |
| **Current complexity** | Medium–High |
| **Navigation pain points** | Label “Pipeline” vs entity “Opportunity” inconsistency |
| **Simplification opportunities** | Standardize term **Opportunity**; keep Pipeline as board view name |

---

## 5. CRM — Products

| Field | Detail |
|-------|--------|
| **Purpose** | Product/service catalog for quotes and invoices |
| **Primary users** | Sales manager, Admin |
| **Related modules** | Quotations, Invoices |
| **Current complexity** | Low–Medium |
| **Navigation pain points** | Sits at same level as transactional CRM items |
| **Simplification opportunities** | Move under CRM → Catalog or Settings → CRM |

---

## 6. CRM — Quotations

| Field | Detail |
|-------|--------|
| **Purpose** | Create and send commercial quotes |
| **Primary users** | Sales |
| **Related modules** | Opportunities, Products, Customers, Invoices |
| **Current complexity** | Medium |
| **Navigation pain points** | Convert-to-invoice path not obvious from nav |
| **Simplification opportunities** | Surface status workflow on list; deep-link from Opportunity |

---

## 7. CRM — Invoices

| Field | Detail |
|-------|--------|
| **Purpose** | Bill customers and track receivables |
| **Primary users** | Finance manager, Sales |
| **Related modules** | Quotations, Payments, Customers, Reports |
| **Current complexity** | Medium |
| **Navigation pain points** | Finance report separate from invoice list |
| **Simplification opportunities** | Group with Payments under **Revenue** sub-nav |

---

## 8. CRM — Payments

| Field | Detail |
|-------|--------|
| **Purpose** | Record and reconcile customer payments |
| **Primary users** | Finance |
| **Related modules** | Invoices, Reports |
| **Current complexity** | Low–Medium |
| **Navigation pain points** | Easy to miss if CRM section collapsed by length |
| **Simplification opportunities** | Pair with Invoices; highlight outstanding on Finance dashboard |

---

## 9. Tasks

| Field | Detail |
|-------|--------|
| **Purpose** | Personal and team work items (standalone or project-linked) |
| **Primary users** | All operational roles |
| **Related modules** | Projects, CRM entities (via activities), Mentions |
| **Current complexity** | High (board, list, timeline, deps, time logs, recurrence) |
| **Navigation pain points** | Nested under CRM in sidebar though cross-cutting |
| **Simplification opportunities** | Promote to **Operations** or Home + Projects; keep CRM-linked tasks on entities |

---

## 10. Projects

| Field | Detail |
|-------|--------|
| **Purpose** | Delivery execution: members, milestones, Gantt, health, budgets, risks, issues |
| **Primary users** | Project manager, Team lead, Department manager |
| **Related modules** | Tasks, Portfolios, Programs, Resources, Customers |
| **Current complexity** | Very High |
| **Navigation pain points** | Only “Projects” and “Resource Planner” in sidebar; portfolios/programs/risks hidden |
| **Simplification opportunities** | Dedicated **Projects workspace** with primary + secondary nav |

---

## 11. Portfolios & Programs

| Field | Detail |
|-------|--------|
| **Purpose** | Strategic grouping and executive oversight of projects |
| **Primary users** | PMO, Executives, Department heads |
| **Related modules** | Projects, Portfolio reports, Forecasts |
| **Current complexity** | High |
| **Navigation pain points** | Routes exist; absent from main sidebar |
| **Simplification opportunities** | Top-level in Projects workspace; progressive disclosure for non-PMO roles |

---

## 12. Resources (Planner)

| Field | Detail |
|-------|--------|
| **Purpose** | Capacity planning, allocations, workload |
| **Primary users** | Project manager, Department manager |
| **Related modules** | Projects, Employees (HR), Tasks |
| **Current complexity** | High |
| **Navigation pain points** | Single “Resource Planner” link under CRM |
| **Simplification opportunities** | Projects workspace → Resources (planner, capacity, timeline, forecast) |

---

## 13. HRMS (Core HR)

| Field | Detail |
|-------|--------|
| **Purpose** | Employees, directory, teams, org structure, announcements, exit, calendar |
| **Primary users** | HR manager, Department manager |
| **Related modules** | Recruitment, ESS, Attendance, Leave, Payroll, Performance |
| **Current complexity** | Very High |
| **Navigation pain points** | Flat list of 15+ HR items; structure config split with Settings hub |
| **Simplification opportunities** | Group: People, Time, Talent, Pay, Hire; move structure to Configuration Hub |

---

## 14. Attendance & Shifts

| Field | Detail |
|-------|--------|
| **Purpose** | Time tracking, shift assignment, corrections |
| **Primary users** | HR, Managers, Employees (via ESS) |
| **Related modules** | Leave, Payroll, ESS |
| **Current complexity** | High |
| **Navigation pain points** | Attendance + Shift Assignments as siblings; rules in Settings |
| **Simplification opportunities** | Single **Time** area with tabs: Attendance | Shifts | Rules |

---

## 15. Leave

| Field | Detail |
|-------|--------|
| **Purpose** | Leave applications, balances, approval queue, holidays |
| **Primary users** | Employees, Managers, HR |
| **Related modules** | Attendance, ESS, Policies (settings) |
| **Current complexity** | High |
| **Navigation pain points** | Four sibling links (dashboard, apps, queue, balances) |
| **Simplification opportunities** | One Leave hub with role tabs (My / Team / Admin) |

---

## 16. Recruitment

| Field | Detail |
|-------|--------|
| **Purpose** | Requisitions, openings, candidates, interviews, offers, careers site |
| **Primary users** | Recruiter, HR manager |
| **Related modules** | Employees (hire), Careers portal, Marketing providers (jobs), Performance |
| **Current complexity** | Very High |
| **Navigation pain points** | Single “Recruitment” link; deep internal IA not reflected in sidebar |
| **Simplification opportunities** | HR workspace subsection or own **Talent** nav: Pipeline | Candidates | Offers | Careers |

---

## 17. Performance & Payroll

| Field | Detail |
|-------|--------|
| **Purpose** | Goals/reviews/appraisals; payroll runs/payslips/statutory |
| **Primary users** | HR manager, Finance (payroll), Managers |
| **Related modules** | Employees, Leave, Attendance |
| **Current complexity** | High each |
| **Navigation pain points** | Single entry each; config mixed with ops |
| **Simplification opportunities** | Separate operational vs configuration; Finance alignment for payroll reporting |

---

## 18. Employee Self-Service (ESS)

| Field | Detail |
|-------|--------|
| **Purpose** | Employee-facing HR: profile, docs, attendance, leave, payroll |
| **Primary users** | Employee |
| **Related modules** | HRMS |
| **Current complexity** | Medium |
| **Navigation pain points** | Parallel labels with HR admin (“Attendance”, “Leave”, “Payroll”) confuse dual-role users |
| **Simplification opportunities** | Prefix “My …” consistently; or hide admin HR when only ESS applies |

---

## 19. Marketing & Providers

| Field | Detail |
|-------|--------|
| **Purpose** | Attribution touches, provider OAuth, tracking webhooks |
| **Primary users** | Marketing ops, Admin |
| **Related modules** | Leads, Integrations, Recruitment careers |
| **Current complexity** | Medium (platform present; UI thin) |
| **Navigation pain points** | No Marketing workspace or sidebar section; only via Integrations |
| **Simplification opportunities** | **Marketing workspace** (campaigns future + providers + attribution); keep credentials under Integrations |

---

## 20. Workflows / Automation

| Field | Detail |
|-------|--------|
| **Purpose** | Cross-module automation rules and executions |
| **Primary users** | Admin, Operations manager |
| **Related modules** | CRM assignments, Project automation, Notifications |
| **Current complexity** | High |
| **Navigation pain points** | Global Workflows in Settings; Project automation separate |
| **Simplification opportunities** | Administration → Automation hub; deep-link module-specific automations |

---

## 21. Metadata

| Field | Detail |
|-------|--------|
| **Purpose** | Custom fields, layouts, blueprints, field permissions |
| **Primary users** | Admin |
| **Related modules** | All entities with custom fields |
| **Current complexity** | Very High |
| **Navigation pain points** | “Metadata Fields” jargon; lives in Settings sidebar |
| **Simplification opportunities** | Rename to **Custom Fields** in Configuration Hub; keep Metadata as advanced/developer term |

---

## 22. Assignments

| Field | Detail |
|-------|--------|
| **Purpose** | Lead/entity assignment pools and rules |
| **Primary users** | Sales manager, Admin |
| **Related modules** | Leads, Workflows |
| **Current complexity** | Medium |
| **Navigation pain points** | Routes exist; not in sidebar |
| **Simplification opportunities** | CRM settings → Assignment rules |

---

## 23. Reports & Finance Analytics

| Field | Detail |
|-------|--------|
| **Purpose** | CRM/finance reporting and exports |
| **Primary users** | Managers, Finance, Executives |
| **Related modules** | Invoices, Payments, Opportunities |
| **Current complexity** | Medium |
| **Navigation pain points** | “Reports” vs “Finance” vs Audit under Analytics; project/portfolio reports elsewhere |
| **Simplification opportunities** | Analytics workspace with report catalog by domain |

---

## 24. Organization & Team

| Field | Detail |
|-------|--------|
| **Purpose** | Org profile, branding, team membership, org switch |
| **Primary users** | Organization owner/admin |
| **Related modules** | Settings, RBAC, Billing |
| **Current complexity** | Medium |
| **Navigation pain points** | Team vs Access Control vs Profile (user) naming |
| **Simplification opportunities** | Administration → Users & Access; Organization under Configuration Hub |

---

## 25. Access Control (RBAC)

| Field | Detail |
|-------|--------|
| **Purpose** | Roles, permissions, templates |
| **Primary users** | Administrator |
| **Related modules** | Team, Settings |
| **Current complexity** | High |
| **Navigation pain points** | Permission groups include Inventory/Support/AI without product surfaces |
| **Simplification opportunities** | Align permission groups to workspaces; hide empty future groups |

---

## 26. Integrations & API Tokens

| Field | Detail |
|-------|--------|
| **Purpose** | External connections and developer tokens |
| **Primary users** | Admin, Developers |
| **Related modules** | Marketing providers, Webhooks |
| **Current complexity** | Medium |
| **Navigation pain points** | Two sidebar entries + settings hub links |
| **Simplification opportunities** | Configuration Hub → Integrations (connections + API) |

---

## 27. Notifications

| Field | Detail |
|-------|--------|
| **Purpose** | In-app notifications and preference controls |
| **Primary users** | All users |
| **Related modules** | Mentions, Workflows |
| **Current complexity** | Medium |
| **Navigation pain points** | Bell in chrome vs org notification settings vs project notification preferences |
| **Simplification opportunities** | Personal prefs in user menu; org defaults in Configuration Hub |

---

## 28. Search

| Field | Detail |
|-------|--------|
| **Purpose** | Global entity find (`/search`) |
| **Primary users** | All |
| **Related modules** | CRM + Projects-heavy; limited HR |
| **Current complexity** | Medium |
| **Navigation pain points** | Header form only; no categories UI, saved searches thin relative to Saved Filters |
| **Simplification opportunities** | See [search-architecture.md](./search-architecture.md) |

---

## 29. Knowledge Center (Documentation)

| Field | Detail |
|-------|--------|
| **Purpose** | In-app docs from `docs/` |
| **Primary users** | All; Admins for enablement |
| **Related modules** | All product modules |
| **Current complexity** | Medium |
| **Navigation pain points** | Under Settings; feels like admin tooling |
| **Simplification opportunities** | Help icon in header; Knowledge as Knowledge category |

---

## 30. Audit Log

| Field | Detail |
|-------|--------|
| **Purpose** | Security/compliance activity trail |
| **Primary users** | Admin, Compliance |
| **Related modules** | Administration |
| **Current complexity** | Low–Medium |
| **Navigation pain points** | Under Analytics |
| **Simplification opportunities** | Move to Administration / Security |

---

## 31. Careers Portal (external)

| Field | Detail |
|-------|--------|
| **Purpose** | Public jobs + candidate portal |
| **Primary users** | Candidates |
| **Related modules** | Recruitment |
| **Current complexity** | High |
| **Navigation pain points** | Separate app surface; settings inside HR recruitment |
| **Simplification opportunities** | Keep external; admin config under HR → Recruitment → Careers |

---

## 32. Platform Console (SaaS)

| Field | Detail |
|-------|--------|
| **Purpose** | Multi-tenant operator console |
| **Primary users** | Platform administrator |
| **Related modules** | Organizations, Industry templates, Platform users |
| **Current complexity** | Medium |
| **Navigation pain points** | Parallel product; not part of tenant IA |
| **Simplification opportunities** | Document as separate product surface; do not mix into tenant workspaces |

---

## Cross-cutting findings

1. **Projects suite is first-class capability with second-class navigation.**
2. **HR density causes cognitive overload** for managers who only need approvals.
3. **Marketing / Assignments / Portfolios** are capability-complete enough to deserve nav homes.
4. **Settings sprawl** — hub + sidebar + module-local config.
5. **Tasks are mis-homed** under CRM.
6. **Future RBAC groups** (Inventory, Support, AI) foreshadow workspaces not yet built.

---

## Audit coverage checklist

| Module area | Audited |
|-------------|---------|
| Dashboard | Yes |
| CRM (leads → payments) | Yes |
| Tasks | Yes |
| Projects / Portfolios / Programs / Resources | Yes |
| HRMS / Leave / Attendance / Performance / Payroll | Yes |
| Recruitment + Careers | Yes |
| ESS | Yes |
| Marketing / Providers | Yes |
| Workflows / Assignments | Yes |
| Metadata | Yes |
| Reports / Finance / Audit | Yes |
| Organization / Team / RBAC | Yes |
| Integrations / API | Yes |
| Notifications / Search / Knowledge | Yes |
| Platform | Yes (adjacent) |
