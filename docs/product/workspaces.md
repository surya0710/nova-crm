# Deliverable 2 — Workspace Architecture

Workspaces are the top-level product containers. A user is always “in” one workspace. Modules live inside workspaces; configuration lives in Administration / Configuration Hub.

---

## Design rules

1. **One primary workspace per task context** — switching workspace changes primary nav, dashboard, and default search scope.
2. **Cross-links allowed** — e.g. Customer → related Projects; never duplicate full module trees.
3. **Role-default workspace** — first login lands on the persona’s home workspace.
4. **Progressive disclosure** — advanced modules appear when permission + plan + org enablement allow.
5. **Future workspaces** may exist in docs/RBAC before UI ships; do not show empty workspaces.

---

## Workspace catalog

### 1. Home

| Field | Definition |
|-------|------------|
| **Purpose** | Personal command center: what needs me now |
| **Target users** | All |
| **Contained modules** | Personal dashboard widgets, My Tasks summary, Notifications, Recent items, Quick actions |
| **Primary dashboard** | `dashboard` / workspace dashboard |
| **Primary navigation** | Home, My Work (tasks), Recents, Notifications |
| **Quick actions** | Create Lead / Task / Leave request (permission-based) |
| **Search scope** | Cross-workspace (default global) |
| **Permissions** | `dashboard.view` + personal scopes |

---

### 2. CRM

| Field | Definition |
|-------|------------|
| **Purpose** | Win and serve revenue relationships |
| **Target users** | Sales executive, Sales manager, Finance (AR), Org owner |
| **Contained modules** | Leads, Customers, Opportunities, Quotations, Invoices, Payments, Products (catalog), CRM Reports |
| **Primary dashboard** | CRM section of Home or dedicated Sales dashboard widgets |
| **Primary navigation** | Leads · Customers · Opportunities · Revenue (Quotations/Invoices/Payments) · Catalog (Products) |
| **Quick actions** | New Lead, New Opportunity, New Quotation, Log Payment |
| **Search scope** | Leads, Customers, Opportunities, Products, Quotations, Invoices, Payments |
| **Permissions** | `leads.*`, `customers.*`, `opportunities.*`, `quotations.*`, `invoices.*`, `payments.*`, `products.*` |

---

### 3. Projects

| Field | Definition |
|-------|------------|
| **Purpose** | Plan and deliver work |
| **Target users** | Project manager, Team lead, PMO, Department manager |
| **Contained modules** | Projects, Tasks (project-linked), Portfolios, Programs, Resources, Risks, Issues, Project/Portfolio Reports |
| **Primary dashboard** | Projects dashboard / Executive project dashboard |
| **Primary navigation** | Projects · Portfolios · Programs · Resources · Risks & Issues · Reports |
| **Quick actions** | New Project, New Task, Log Progress, Allocate Resource |
| **Search scope** | Projects, Tasks, Portfolios, Programs, Risks, Issues, Budgets, Mentions |
| **Permissions** | `projects.*`, `tasks.*`, `resources.*`, portfolio/program permissions |

---

### 4. HR

| Field | Definition |
|-------|------------|
| **Purpose** | Hire, manage, and pay people |
| **Target users** | HR manager, Recruiter, Department manager, Payroll ops |
| **Contained modules** | Employees, Directory, Teams, Attendance, Leave, Recruitment, Performance, Payroll, Announcements, Exit, Calendar |
| **Primary dashboard** | HR Dashboard; Managers use Manager Dashboard |
| **Primary navigation** | People · Time · Leave · Recruitment · Performance · Payroll |
| **Quick actions** | Add Employee, Post Opening, Approve Leave, Run Payroll |
| **Search scope** | Employees, Candidates, Job Openings, Leave Applications (when search extended) |
| **Permissions** | `hrms.*`, `recruitment.*`, `attendance.*`, `leave.*`, `payroll.*`, `performance.*` |

**Self-Service note:** ESS is a **mode** of HR for employees (`ess.access`), not a separate workspace. Label: **My HR** within HR or Home.

---

### 5. Marketing

| Field | Definition |
|-------|------------|
| **Purpose** | Acquire and attribute demand |
| **Target users** | Marketing ops, Growth, Admin |
| **Contained modules** | Attribution / touches, Providers (UI), Campaigns (future), Lead sources |
| **Primary dashboard** | Marketing widgets (future) + CRM lead source insights |
| **Primary navigation** | Attribution · Providers · Campaigns (future) |
| **Quick actions** | Connect Provider, View Lead Sources |
| **Search scope** | Marketing entities when available; else CRM leads with source |
| **Permissions** | `marketing.*`, `integrations.*` (subset) |

---

### 6. Operations

| Field | Definition |
|-------|------------|
| **Purpose** | Cross-functional daily work not owned by CRM/Projects/HR |
| **Target users** | Operations manager, Team leads, multi-module users |
| **Contained modules** | Standalone Tasks, Assignments (ops view), Approvals inbox (future), Inventory (future) |
| **Primary dashboard** | Ops widgets: open tasks, pending approvals |
| **Primary navigation** | Tasks · Approvals · Assignments |
| **Quick actions** | New Task |
| **Search scope** | Tasks + assignment-related |
| **Permissions** | `tasks.*`, `assignments.*` |

*MVP:* Operations may start as Home “My Work” until Approvals/Inventory ship.

---

### 7. Analytics

| Field | Definition |
|-------|------------|
| **Purpose** | Organization-wide insight |
| **Target users** | CEO, Department head, Finance manager, Org owner |
| **Contained modules** | Reports catalog, Finance reports, Executive dashboards, Audit (read) |
| **Primary dashboard** | Executive / multi-module analytics views |
| **Primary navigation** | Overview · Sales · Delivery · People · Finance · Audit |
| **Quick actions** | Export report, Save view |
| **Search scope** | Report titles + deep-link entities |
| **Permissions** | `reports.view`, `finance.view`, `audit.view`, executive project perms |

---

### 8. Administration

| Field | Definition |
|-------|------------|
| **Purpose** | Control the tenant |
| **Target users** | Organization administrator, Security-minded owners |
| **Contained modules** | Users/Team, Access Control, Subscription/Billing, Audit management, Org switcher admin |
| **Primary dashboard** | Admin health: users, seats, failed jobs (future) |
| **Primary navigation** | Users · Roles · Billing · Security · Audit |
| **Quick actions** | Invite User, Create Role |
| **Search scope** | Users, Roles |
| **Permissions** | `users.*`, `rbac.*`, `settings.manage`, `audit.view` |

---

## Configuration Hub (not a workspace)

Cross-cutting **Configuration** destination opened from Administration or gear icon.

Contains: Organization profile, Structure, HR policies, CRM assignment rules, Custom Fields, Workflows, Integrations, Notifications defaults, Module catalogs (products, leave types, project statuses, etc.).

See [settings-architecture.md](./settings-architecture.md).

---

## Future workspaces

| Workspace | Purpose | Contained modules (planned) | Trigger to show |
|-----------|---------|-----------------------------|-----------------|
| **Finance** | Beyond AR invoices | Expenses, GL, Payouts | Finance module GA |
| **Support** | Customer service | Tickets, SLAs, Knowledge base ops | Support module GA |
| **Assets** | Asset lifecycle | IT/HR assets | Assets production-ready |

---

## Workspace ↔ module map (primary)

```
Home          → Dashboard, Notifications, Recents
CRM           → Leads, Customers, Opportunities, Quotes, Invoices, Payments, Products*
Projects      → Projects, Portfolios, Programs, Resources, Risks, Issues, Project Tasks
HR            → Employees, Time, Leave, Recruitment, Performance, Payroll, ESS
Marketing     → Attribution, Providers UI
Operations    → Standalone Tasks, Assignments
Analytics     → Reports, Finance reports, Exec views, Audit (read)
Administration→ Users, RBAC, Billing, Security

* Products also listed under Configuration Hub as Catalog
```

---

## Default landing by persona

| Persona | Default workspace |
|---------|-------------------|
| Organization Owner / Admin | Home (admin widgets) or Administration |
| CEO | Analytics |
| Sales Manager / Executive | CRM |
| Project Manager | Projects |
| HR Manager / Recruiter | HR |
| Operations Manager | Operations or Home |
| Employee | Home → My HR |
| Support Agent (future) | Support |
| Platform Admin | Platform console (outside tenant) |

---

## Permissions model

- Workspace visibility = **union of module permissions** inside it.
- Empty workspaces are hidden.
- Switching workspace does not change org context; org switcher remains global.
- Plan gates (`config/dashboard.php` `plan_modules`) continue to constrain widgets/modules inside workspaces.
