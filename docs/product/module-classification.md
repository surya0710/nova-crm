# Deliverable 3 — Module Classification

Every module belongs to **exactly one** primary category. Secondary relationships are noted but do not change primary ownership.

---

## Categories

| Category | Definition | User mental model |
|----------|------------|-------------------|
| **Operational** | Day-to-day create/update work | “I do my job here” |
| **Management** | Oversight, planning, coordination | “I run the team/portfolio here” |
| **Analytics** | Insights, reports, audits for decisions | “I understand performance here” |
| **Administration** | Users, security, org membership | “I control access here” |
| **Configuration** | System/module setup and policies | “I set how the system behaves” |
| **Knowledge** | Help, documentation, learning | “I learn how to use the product” |
| **Automation** | Rules, workflows, assignment engines | “The system acts for me here” |
| **Integration** | External systems, APIs, providers | “We connect other tools here” |

---

## Classification matrix

| Module | Primary category | Notes |
|--------|------------------|-------|
| Home / Workspace Dashboard | Operational | Personal landing; widgets may pull Analytics |
| Leads | Operational | |
| Customers | Operational | |
| Opportunities (Pipeline) | Operational | Board is a view, not a category |
| Products | Configuration | Catalog master data; ops consume via quotes |
| Quotations | Operational | |
| Invoices | Operational | |
| Payments | Operational | |
| Tasks | Operational | Cross-workspace utility |
| Projects | Operational | Delivery work |
| Project Milestones / Progress / Gantt | Operational | Entity context |
| Project Risks / Issues | Operational | |
| Project Budgets / Baselines | Management | Planning controls |
| Portfolios | Management | |
| Programs | Management | |
| Resource Planner | Management | Capacity planning |
| Employees | Operational | HR ops |
| Directory | Operational | Read-heavy ops |
| Teams (HR) | Management | |
| Announcements | Operational | |
| Exit Processes | Operational | |
| Organization Calendar (HR) | Operational | |
| Attendance | Operational | |
| Shift Assignments | Operational | |
| Leave Applications / Balances | Operational | |
| Leave Approval Queue | Management | Manager oversight |
| Recruitment Pipeline | Operational | |
| Candidates / Interviews / Offers | Operational | |
| Careers Site Settings | Configuration | |
| Performance Reviews / Goals | Operational | |
| Performance Calibration / Talent Matrix | Management | |
| Payroll Runs | Operational | |
| Payroll Statutory Config | Configuration | |
| ESS (My HR) | Operational | Employee-scoped |
| Marketing Attribution / Touches | Operational | When Marketing workspace ships |
| Marketing Providers | Integration | Credentials live under Integration |
| Workflows | Automation | |
| Project Automation | Automation | Nested under Projects UX, category Automation |
| Assignment Rules | Automation | |
| Metadata / Custom Fields | Configuration | |
| Reports (CRM) | Analytics | |
| Finance Reports | Analytics | |
| Portfolio / Project Reports | Analytics | |
| Audit Log | Analytics | Security analytics; admin-consumed |
| Organization Profile / Branding | Configuration | |
| Subscription / Billing | Administration | Commercial admin |
| Branches / Departments / Designations | Configuration | Org structure |
| Working Days / Leave Policies / Attendance Rules | Configuration | |
| Access Control (RBAC) | Administration | |
| Team (Users) | Administration | |
| API Tokens | Integration | |
| Integrations Hub | Integration | |
| Notifications (in-app) | Operational | Personal consumption |
| Notification Org Defaults | Configuration | |
| Notification Preferences (user) | Configuration | Personal |
| Global Search | Operational | Cross-cutting find |
| Knowledge Center | Knowledge | |
| Saved Filters | Operational | View utility |
| Platform Console | Administration | Separate product surface |

---

## Future modules (pre-classified)

| Future module | Category | Target workspace |
|---------------|----------|------------------|
| Finance (GL / expenses) | Operational | Finance |
| Support / Tickets | Operational | Support |
| Assets (IT / HR assets) | Operational | HR or Assets |
| Inventory | Operational | Operations |
| AI Assistants | Automation | Cross-cutting |
| Campaigns (Marketing) | Operational | Marketing |
| Helpdesk SLA policies | Configuration | Support |

---

## Rules for new modules

1. Pick **one** primary category before adding routes.
2. Map to **one** primary workspace ([workspaces.md](./workspaces.md)).
3. If users both *operate* and *configure*, split screens: ops in workspace nav, config in Configuration Hub.
4. Do not place Automation or Integration items in Operational primary nav for first-time users.
5. Analytics modules never own create/edit of core records (except annotations).

---

## Permission grouping alignment (target)

Map RBAC groups toward categories/workspaces (Phase 14+):

| Current permission group | Target alignment |
|--------------------------|------------------|
| CRM / Leads / Customers / Opportunities / … | CRM workspace + Operational |
| Projects / Tasks | Projects workspace |
| HRMS / Recruitment | HR workspace |
| Marketing | Marketing workspace |
| Finance | Analytics + future Finance workspace |
| Workflow | Automation |
| Integrations / API | Integration |
| Settings / Metadata | Configuration |
| Administration / Users / RBAC | Administration |
| Reports | Analytics |
| Inventory / Support / AI | Future workspaces (hide until live) |
