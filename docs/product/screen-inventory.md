# Deliverable 4 — Screen Inventory

Inventory of primary application screens. Routes reflect current tenant app unless noted. Priority: **P0** (core daily) · **P1** (weekly) · **P2** (admin/occasional) · **P3** (rare/advanced). Redesign candidate: **Yes** / **Partial** / **No**.

Frequency: **Daily** · **Weekly** · **Monthly** · **Rare**.

---

## Home & shell

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| Workspace Dashboard | Home | Landing widgets | All | `dashboard` | Sidebar Main | Widgets, perms, plan | Daily | Partial | P0 |
| Global Search Results | Search | Find entities | All | `search.index` | Header | SearchService | Daily | Yes | P0 |
| Notifications | Notifications | Inbox | All | `notifications.*` | Header | Notification models | Daily | Partial | P0 |
| Profile | Account | User profile | All | `profile.edit` | Settings / user menu | User | Rare | No | P2 |
| Knowledge Center | Knowledge | Docs | All | `knowledge.*` | Settings (today) | `docs/` | Weekly | Partial | P1 |
| Organization Setup | Onboarding | First org | Owner | `organization.setup` | Auth flow | Organization | Rare | Partial | P1 |

---

## CRM

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| Leads Index | Leads | List/filter leads | Sales | `leads.index` | CRM | Lead, filters | Daily | Partial | P0 |
| Lead Show/Edit | Leads | Lead detail | Sales | `leads.show/edit` | Entity | Notes, metadata | Daily | Partial | P0 |
| Lead Create | Leads | New lead | Sales | `leads.create` | CRM | Assignment | Daily | No | P0 |
| Lead Import | Leads | Bulk import | Sales mgr | `leads.import.*` | Hidden | ImportSession | Weekly | Partial | P1 |
| Customers Index | Customers | Account list | Sales/Finance | `customers.index` | CRM | Customer | Daily | Partial | P0 |
| Customer Show | Customers | Account 360 | Sales | `customers.show` | Entity | Quotes, invoices | Daily | Yes | P0 |
| Pipeline Board/List | Opportunities | Deal pipeline | Sales | `pipeline.index` | CRM | Opportunity | Daily | Partial | P0 |
| Opportunity Show | Opportunities | Deal detail | Sales | `pipeline.show` | Entity | Products, quotes | Daily | Partial | P0 |
| Products Index | Products | Catalog | Sales mgr | `products.index` | CRM | Product | Weekly | Partial | P1 |
| Quotations Index/Show | Quotations | Quotes | Sales | `quotations.*` | CRM | Customer, products | Daily | Partial | P0 |
| Invoices Index/Show | Invoices | Billing | Finance/Sales | `invoices.*` | CRM | Customer | Daily | Partial | P0 |
| Payments Index/Show | Payments | Receipts | Finance | `payments.*` | CRM | Invoice | Daily | Partial | P0 |
| Assignment Settings | Assignments | Routing rules | Sales mgr | `assignments.*` | Hidden | Pools, rules | Monthly | Yes | P1 |
| Saved Filters | CRM utility | Persist views | Power users | `saved-filters.*` | In-list | SavedFilter | Weekly | No | P2 |

---

## Tasks

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| Tasks Index/Board/List/Timeline | Tasks | Work management | All ops | `tasks.index` + views | CRM (today) | Task, status, priority | Daily | Yes | P0 |
| Task Show | Tasks | Task detail | Assignee | `tasks.show` | Entity | Comments, time, deps | Daily | Partial | P0 |
| Task Statuses/Priorities | Tasks | Catalog config | Admin | `task-statuses.*`, `task-priorities.*` | Hidden/settings | Catalogs | Rare | Partial | P2 |

---

## Projects suite

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| Projects Index | Projects | Project list | PM | `projects.index` | CRM → Projects | Project | Daily | Yes | P0 |
| Projects Dashboard | Projects | Ops overview | PM | `projects.dashboard` | In-module | Health, progress | Daily | Partial | P0 |
| Projects Executive | Projects | Exec view | Exec | `projects.executive` | Hidden | Aggregates | Weekly | Partial | P1 |
| Project Show Hub | Projects | Project home | Team | `projects.show` | Entity | Members, milestones | Daily | Yes | P0 |
| Gantt / Timeline | Projects | Schedule | PM | `projects.gantt` etc. | Entity | Tasks, deps | Weekly | Partial | P1 |
| Progress Dashboard | Projects | Status updates | PM | `projects.progress.dashboard` | Entity | ProgressUpdate | Weekly | Partial | P1 |
| Health | Projects | Health score | PM/Exec | `projects.health.*` | Entity | Snapshots | Weekly | No | P1 |
| Risks / Issues Index | Projects | Risk/issue mgmt | PM | `risks.*`, `issues.*` | Hidden | Risk, Issue | Weekly | Yes | P1 |
| Budgets / Baselines | Projects | Financial control | PM | nested `projects.*` | Entity | Budget models | Monthly | Partial | P1 |
| Portfolios Index/Dashboard | Portfolios | Portfolio mgmt | PMO | `portfolios.*` | Hidden | Portfolio | Weekly | Yes | P1 |
| Portfolio Executive/Forecast | Portfolios | Strategy | Exec | `portfolios.executive`, forecasts | Hidden | Forecasts | Monthly | Yes | P1 |
| Programs | Programs | Program mgmt | PMO | `programs.*` | Hidden | Program | Weekly | Yes | P1 |
| Resource Planner | Resources | Capacity | PM | `resources.planner` | CRM | Allocations | Daily | Yes | P0 |
| Resource Capacity/Timeline/Forecast | Resources | Planning views | PM | `resources.*` | Hidden | Calendars | Weekly | Yes | P1 |
| Project Catalogs | Projects | Categories/types/statuses | Admin | `project-*` catalogs | Hidden | Catalogs | Rare | Partial | P2 |
| Templates | Projects | Project templates | PM/Admin | `project-templates.*` / labels | Hidden | Templates | Monthly | Partial | P2 |
| Project Automation | Automation | Project rules | PM/Admin | `projects.automation` | Entity/settings | Workflows | Rare | Partial | P2 |
| Collaboration / Mentions | Projects | Collab | Team | `mentions.*` | Hidden | Mentions | Daily | Partial | P1 |
| Portfolio Reports | Analytics | Portfolio reporting | PMO | `portfolio-reports.*` | Hidden | Reports | Monthly | Partial | P2 |

---

## HRMS

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| HR Dashboard | HR | HR overview | HR | `hrms.dashboard` | HR | Aggregates | Daily | Partial | P0 |
| Manager Dashboard | HR | Team oversight | Manager | `hrms.manager.dashboard` | HR | Team data | Daily | Partial | P0 |
| Directory | HR | People lookup | All HR | `hrms.directory.index` | HR | Employee | Daily | No | P0 |
| Employees Index/Show | HR | Employee records | HR | `hrms.employees.*` | HR | Employee | Daily | Partial | P0 |
| Teams | HR | Team structure | HR/Mgr | `hrms.teams.*` | HR | HrmsTeam | Weekly | No | P1 |
| Announcements | HR | Comms | HR | `hrms.announcements.*` | HR | Announcements | Weekly | No | P1 |
| Exit Processes | HR | Offboarding | HR | `hrms.exit-processes.*` | HR | Exit | Monthly | Partial | P1 |
| HR Calendar | HR | Org calendar | HR | `hrms.calendar` | HR | Holidays, leave | Weekly | No | P1 |
| Attendance Index | Attendance | Attendance ops | HR | `hrms.attendance.*` | HR | AttendanceRecord | Daily | Partial | P0 |
| Shift Assignments | Attendance | Shift roster | HR | `hrms.shift-assignments.*` | HR | Shifts | Weekly | Partial | P1 |
| Leave Dashboard | Leave | Leave overview | HR | `hrms.leave.dashboard` | HR | Leave | Daily | Yes | P0 |
| Leave Applications | Leave | Requests | HR/Emp | `hrms.leave-applications.*` | HR | Applications | Daily | Yes | P0 |
| Approval Queue | Leave | Approvals | Manager | `hrms.leave-applications.approval-queue` | HR | Approvers | Daily | Partial | P0 |
| Leave Balances | Leave | Balances | HR | `hrms.leave-balances.*` | HR | Balances | Weekly | Partial | P1 |
| Branches/Departments/Designations | Structure | Org structure | HR Admin | `hrms.branches|departments|designations` | Settings hub redirects | Structure models | Monthly | Partial | P1 |
| Shifts / Holidays / Leave Types | Time config | Policies | HR Admin | via settings redirects | Settings hub | Config models | Monthly | Partial | P1 |

---

## Recruitment

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| Recruitment Dashboard | Recruitment | Hiring overview | Recruiter | `hrms.recruitment.dashboard` | HR → Recruitment | Pipeline | Daily | Yes | P0 |
| Executive / Analytics | Recruitment | Hiring insights | HR lead | `hrms.recruitment.executive|analytics` | In-module | Metrics | Weekly | Partial | P1 |
| Requisitions / Openings | Recruitment | Job demand | Recruiter | `hrms.recruitment.*` | In-module | Job models | Daily | Partial | P0 |
| Candidates / Applications | Recruitment | Talent pipeline | Recruiter | nested recruitment | In-module | Candidate | Daily | Yes | P0 |
| Interviews / Evaluations | Recruitment | Interview process | Recruiter/Hiring mgr | nested | In-module | Stages | Daily | Partial | P0 |
| Offers | Recruitment | Offer management | Recruiter | nested offers | In-module | OfferLetter | Weekly | Partial | P0 |
| Careers / Portal Settings | Recruitment | Public site config | HR Admin | careers/portal settings | In-module | CareerSiteSetting | Monthly | Partial | P1 |
| Careers Public Home | Careers | Job board | Candidate | `careers.home` | External | Org slug | Daily | Partial | P0 |
| Candidate Portal Dashboard | Careers | Applicant ESS | Candidate | `careers.dashboard` | External | CandidateAccount | Daily | Partial | P0 |

---

## Performance & Payroll

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| Performance Index | Performance | Cycles/reviews | HR/Mgr | `hrms.performance.*` | HR | Performance models | Weekly | Partial | P1 |
| Payroll Index | Payroll | Runs/payslips | Payroll | `hrms.payroll.*` | HR | Payroll models | Weekly | Partial | P0 |

---

## ESS

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| My HR | ESS | Employee home | Employee | `ess.dashboard` | Self-Service | ESS perms | Daily | Partial | P0 |
| ESS Profile/Docs/Attendance/Leave/Payroll | ESS | Self-service | Employee | `ess.*` | Self-Service | Employee link | Daily | Partial | P0 |

---

## Analytics

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| Reports Index | Reports | CRM reports | Manager | `reports.index` | Analytics | ReportController | Weekly | Yes | P1 |
| Finance Report | Finance | AR/revenue | Finance | `reports.finance` | Analytics | Invoices/Payments | Weekly | Yes | P1 |
| Audit Log | Audit | Compliance trail | Admin | `audit-logs.index` | Analytics | Audit | Weekly | Partial | P1 |

---

## Automation, Metadata, Integrations

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| Workflows Index/Show | Workflows | Automations | Admin | `workflows.*` | Settings | Workflow models | Weekly | Partial | P1 |
| Metadata Fields | Metadata | Custom fields | Admin | `metadata-fields.*` | Settings | Field definitions | Monthly | Yes | P1 |
| Integrations Index | Integrations | Connections | Admin | `integrations.*` | Settings | Providers | Monthly | Partial | P1 |
| Marketing Providers OAuth | Marketing | Connect providers | Admin | `marketing.providers.*` | Via Integrations | OAuth | Rare | Partial | P2 |
| API Tokens | Integration | Developer tokens | Admin | `api-tokens.*` | Settings | Sanctum tokens | Rare | No | P2 |

---

## Administration & Settings

| Screen | Module | Purpose | Primary user | Route | Nav location | Dependencies | Frequency | Redesign | Priority |
|--------|--------|---------|--------------|-------|--------------|--------------|-----------|----------|----------|
| Settings Hub | Configuration | Settings home | Admin | `organization.settings.hub` | Settings | `organization_settings.php` | Weekly | Yes | P0 |
| Organization Edit | Configuration | Profile/brand/email | Admin | `organization.edit` | Hub | Organization | Monthly | Partial | P1 |
| Subscription / Billing | Administration | Plan/billing | Owner | `organization.settings.subscription|billing` | Hub | Billing | Monthly | Partial | P1 |
| Working Days / Leave Policies / Approvers / Attendance Rules | Configuration | HR policy | HR Admin | `organization.settings.*` | Hub | Policy models | Monthly | Partial | P1 |
| Notifications Settings | Configuration | Org defaults | Admin | `organization.settings.notifications.edit` | Hub | Settings | Monthly | Partial | P2 |
| RBAC Roles/Permissions | Administration | Access control | Admin | `rbac.*` | Settings | Role, Permission | Monthly | Partial | P0 |
| Team Index | Administration | Users | Admin | `team.index` | Settings | User-org | Weekly | Partial | P0 |
| Notification Preferences | Configuration | User prefs | All | `notification-preferences.*` | Hidden | Prefs | Rare | Partial | P2 |

---

## Platform (adjacent)

| Screen | Module | Purpose | Primary user | Route | Nav | Priority |
|--------|--------|---------|--------------|-------|-----|----------|
| Platform Dashboard | Platform | Operator home | Platform admin | `platform.dashboard` | Platform sidebar | P1 |
| Organizations | Platform | Tenant mgmt | Platform admin | `platform.organizations.*` | Platform | P0 |
| Industry Templates | Platform | Templates | Platform admin | `platform.industry-templates.*` | Platform | P1 |
| Platform Users / Audit / Reports | Platform | Ops | Platform admin | `platform.*` | Platform | P1 |

---

## Inventory summary

| Area | Screen count (approx.) | P0 share | Hidden from main nav |
|------|------------------------|----------|----------------------|
| CRM + Tasks | ~25 | High | Imports, assignments, catalogs |
| Projects suite | ~35+ | Medium | Portfolios, programs, risks, many entity tabs |
| HR + Recruitment + ESS | ~40+ | High | Deep recruitment, structure |
| Analytics / Admin / Config | ~25 | Medium | Prefs, provider OAuth |
| External | Careers + Platform | — | Separate shells |

**Ownership rule:** Every screen’s primary owner is the workspace listed in [workspaces.md](./workspaces.md). Entity screens inherit the parent module’s workspace.
