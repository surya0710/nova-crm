# Deliverable 10 — User Personas

Personas for NovaCRM product design. Aligned with system roles in `config/dynamic_rbac.php` and operational reality.

---

## 1. Organization Owner

| Field | Detail |
|-------|--------|
| **Goals** | Grow the business; keep the tenant healthy; control cost and access |
| **Daily tasks** | Review Home KPIs, approve billing, check critical alerts, occasional CRM glance |
| **Frequent modules** | Home, Analytics, Administration, Billing, CRM (overview) |
| **Navigation needs** | Fast Admin + Analytics; uncluttered ops nav |
| **Dashboard needs** | Executive cross-module Home; subscription health |
| **Permissions** | Organization administrator (`*` / settings, users, rbac, billing) |

---

## 2. CEO / Executive

| Field | Detail |
|-------|--------|
| **Goals** | See pipeline, delivery risk, hiring, and cash without operating records |
| **Daily tasks** | Scan executive dashboards, drill into exceptions |
| **Frequent modules** | Analytics, Projects executive, Recruitment executive, Finance reports |
| **Navigation needs** | Analytics-first; minimal create actions |
| **Dashboard needs** | Executive dashboards; portfolio views |
| **Permissions** | Reports, finance.view, projects.executive.view, recruitment executive |

---

## 3. Operations Manager

| Field | Detail |
|-------|--------|
| **Goals** | Keep cross-team work flowing; clear bottlenecks |
| **Daily tasks** | Task triage, assignment oversight, follow-ups across CRM/Projects |
| **Frequent modules** | Operations (Tasks), CRM (spot checks), Projects, Workflows |
| **Navigation needs** | Tasks + Approvals + cross-workspace search |
| **Dashboard needs** | Open tasks, SLA-ish queues, workflow failures |
| **Permissions** | tasks.*, assignments.*, workflows.view, relevant CRM view |

---

## 4. HR Manager

| Field | Detail |
|-------|--------|
| **Goals** | Accurate people data; compliant leave/attendance; healthy hiring and payroll |
| **Daily tasks** | Employee updates, leave oversight, announcements, payroll coordination |
| **Frequent modules** | HR (People, Leave, Attendance, Payroll, Performance), Recruitment (oversight) |
| **Navigation needs** | Grouped HR nav; Configuration for policies |
| **Dashboard needs** | HR Dashboard |
| **Permissions** | hrms.*, leave.*, attendance.*, payroll.*, performance.*, recruitment.view |

---

## 5. Recruiter

| Field | Detail |
|-------|--------|
| **Goals** | Fill openings fast with quality candidates |
| **Daily tasks** | Advance candidates, schedule interviews, send offers, update careers posts |
| **Frequent modules** | Recruitment (all), Directory/Employees (on hire), Email/notifications |
| **Navigation needs** | Recruitment children as primary; pin Candidates |
| **Dashboard needs** | Recruitment dashboard |
| **Permissions** | recruitment.*, limited hrms on hire |

---

## 6. Project Manager

| Field | Detail |
|-------|--------|
| **Goals** | Deliver projects on time/budget; manage risk and resources |
| **Daily tasks** | Update plans, review Gantt, allocate resources, log risks/issues, progress |
| **Frequent modules** | Projects, Tasks, Resources, Risks/Issues, Portfolios (light) |
| **Navigation needs** | Projects workspace complete; Resources visible |
| **Dashboard needs** | Projects dashboard; per-project progress |
| **Permissions** | projects.*, tasks.*, resources.* |

---

## 7. Sales Manager

| Field | Detail |
|-------|--------|
| **Goals** | Hit team quota; fair lead distribution; forecast accuracy |
| **Daily tasks** | Pipeline review, coaching deals, assignment rules, forecasts |
| **Frequent modules** | CRM (all), Reports, Assignments, Team |
| **Navigation needs** | CRM Revenue + Pipeline; Reports shortcut |
| **Dashboard needs** | Team pipeline widgets |
| **Permissions** | CRM manage-level, reports.view, assignments |

---

## 8. Sales Executive

| Field | Detail |
|-------|--------|
| **Goals** | Convert leads; close opportunities; keep quotes/invoices moving |
| **Daily tasks** | Work leads, update opportunities, create quotations, follow invoices |
| **Frequent modules** | Leads, Customers, Opportunities, Quotations, Tasks |
| **Navigation needs** | Short CRM list; create CTAs; search |
| **Dashboard needs** | My Leads, My Pipeline |
| **Permissions** | leads/customers/opportunities/quotations (+ invoices as allowed) |

---

## 9. Support Agent (future-ready)

| Field | Detail |
|-------|--------|
| **Goals** | Resolve customer issues quickly |
| **Daily tasks** | Ticket queue, customer lookup, escalate |
| **Frequent modules** | Support (future), Customers, Knowledge |
| **Navigation needs** | Support workspace; Customer 360 |
| **Dashboard needs** | My open tickets |
| **Permissions** | support.* (when shipped), customers.view |

---

## 10. Employee

| Field | Detail |
|-------|--------|
| **Goals** | Manage personal HR without admin tools |
| **Daily tasks** | Punch/attendance, leave apply, view payslip, update profile |
| **Frequent modules** | My HR (ESS), Home, Knowledge (rare) |
| **Navigation needs** | Only Home + My HR |
| **Dashboard needs** | ESS dashboard |
| **Permissions** | ess.access |

---

## 11. Department Head

| Field | Detail |
|-------|--------|
| **Goals** | Department performance across people and projects |
| **Daily tasks** | Manager dashboard, approvals, portfolio glance, hiring input |
| **Frequent modules** | Manager Dashboard, Leave approvals, Projects (dept), Analytics |
| **Navigation needs** | HR manager views + Projects + Analytics |
| **Dashboard needs** | Manager + executive project views |
| **Permissions** | manager.dashboard, leave.approve, projects view, reports |

---

## 12. Administrator

| Field | Detail |
|-------|--------|
| **Goals** | Secure, correctly configured tenant |
| **Daily tasks** | Users/roles, custom fields, workflows, integrations, audit review |
| **Frequent modules** | Administration, Configuration Hub, Audit, Metadata, Workflows |
| **Navigation needs** | Administration always visible; Config Hub |
| **Dashboard needs** | Admin health (future); Audit shortcuts |
| **Permissions** | rbac.*, settings.manage, metadata.*, workflows.*, integrations.*, audit.view |

---

## 13. Finance Manager

| Field | Detail |
|-------|--------|
| **Goals** | Collect receivables; understand revenue |
| **Daily tasks** | Invoices, payments, finance reports, payroll liaison |
| **Frequent modules** | Invoices, Payments, Finance reports, Payroll (view) |
| **Navigation needs** | CRM Revenue + Analytics Finance |
| **Dashboard needs** | Outstanding AR widgets |
| **Permissions** | invoices.*, payments.*, finance.view, reports.view |

---

## 14. Platform Administrator (adjacent)

| Field | Detail |
|-------|--------|
| **Goals** | Operate multi-tenant SaaS |
| **Daily tasks** | Org provisioning, templates, impersonation, platform audit |
| **Frequent modules** | Platform console |
| **Navigation needs** | Platform sidebar only |
| **Dashboard needs** | Platform dashboard |
| **Permissions** | PlatformUser auth (outside org RBAC) |

---

## Persona → default workspace

| Persona | Default workspace |
|---------|-------------------|
| Organization Owner | Home |
| CEO | Analytics |
| Operations Manager | Operations / Home |
| HR Manager | HR |
| Recruiter | HR |
| Project Manager | Projects |
| Sales Manager | CRM |
| Sales Executive | CRM |
| Support Agent | Support (future) |
| Employee | Home |
| Department Head | Home |
| Administrator | Administration |
| Finance Manager | CRM or Analytics |
| Platform Administrator | Platform |

---

## Design implications

1. **Sidebar length must vary by persona** — Employee ≠ Admin.  
2. **Dual-role users** (manager who is also employee) need clear My HR vs Manage HR.  
3. **Executives** should not hunt through operational lists for dashboards.  
4. **Glossary + consistent CTAs** reduce training for Sales Executive and Employee.
