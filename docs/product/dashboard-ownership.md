# Deliverable 9 — Dashboard Ownership

Defines who owns each dashboard, what it optimizes for, and how dashboards relate to workspaces.

---

## Ownership model

| Owner type | Responsibility |
|------------|----------------|
| **Product owner (workspace)** | Default layout, widget catalog for that workspace |
| **Role owner** | Default widgets for a persona |
| **User** | Personal layout preferences (existing `UserDashboardPreference`) |
| **Organization admin** | Org-enabled widgets / quick actions (`OrganizationDashboardWidget`, `OrganizationQuickAction`) |
| **Platform** | Plan module gates (`config/dashboard.php` `plan_modules`) |

Conflict order: Plan gate → Permission → Org enablement → Role default → User preference.

---

## Dashboard catalog

### 1. Home (Workspace Dashboard)

| Field | Value |
|-------|-------|
| **Route** | `dashboard` / `dashboard.workspace` |
| **Owner** | Home workspace product + User |
| **Audience** | All members |
| **Purpose** | “What needs me today” |
| **Type** | Personal + role-aware |
| **Key widgets** | Welcome, Notifications, Calendar, My Leads, My Tasks, Approvals (future) |

---

### 2. CRM / Sales operational

| Field | Value |
|-------|-------|
| **Surfacing** | Home CRM section widgets + future CRM workspace home |
| **Owner** | CRM workspace |
| **Audience** | Sales executive, Sales manager |
| **Purpose** | Pipeline health, personal funnel |
| **Type** | Operational |
| **Key widgets** | My Leads, Pipeline, Quotations due, Revenue snapshot |

---

### 3. Projects Dashboard

| Field | Value |
|-------|-------|
| **Route** | `projects.dashboard` |
| **Owner** | Projects workspace |
| **Audience** | Project managers, members |
| **Purpose** | Delivery status across my projects |
| **Type** | Operational |
| **Key widgets** | Active projects, At-risk, Upcoming milestones, Workload |

---

### 4. Projects Executive Dashboard

| Field | Value |
|-------|-------|
| **Route** | `projects.executive` |
| **Owner** | Projects workspace (executive view) |
| **Audience** | Executives, Department heads, PMO |
| **Purpose** | Portfolio-level delivery risk and investment |
| **Type** | Executive |
| **Permission** | `projects.executive.view` |

---

### 5. Portfolio / Program Dashboards

| Field | Value |
|-------|-------|
| **Routes** | `portfolios.dashboard`, `portfolios.executive`, `programs.dashboard` |
| **Owner** | PMO / Projects management |
| **Audience** | PMO, Executives |
| **Purpose** | Strategic grouping performance |
| **Type** | Executive / Management |

---

### 6. Per-Project Progress Dashboard

| Field | Value |
|-------|-------|
| **Route** | `projects.progress.dashboard` |
| **Owner** | Project manager of that project |
| **Audience** | Project team |
| **Purpose** | Single-project execution pulse |
| **Type** | Operational (entity-scoped) |

---

### 7. HR Dashboard

| Field | Value |
|-------|-------|
| **Route** | `hrms.dashboard` |
| **Owner** | HR workspace |
| **Audience** | HR managers |
| **Purpose** | Headcount, leave load, hiring pulse |
| **Type** | Operational / Department |

---

### 8. Manager Dashboard

| Field | Value |
|-------|-------|
| **Route** | `hrms.manager.dashboard` |
| **Owner** | HR workspace (manager view) |
| **Audience** | Department managers |
| **Purpose** | Team attendance, leave approvals, direct reports |
| **Type** | Department |
| **Permission** | `manager.dashboard` |

---

### 9. Leave Dashboard

| Field | Value |
|-------|-------|
| **Route** | `hrms.leave.dashboard` |
| **Owner** | HR → Leave |
| **Audience** | HR leave admins |
| **Purpose** | Leave operations overview |
| **Type** | Operational |

---

### 10. Recruitment Dashboards

| Field | Value |
|-------|-------|
| **Routes** | `hrms.recruitment.dashboard`, `.executive`, `.analytics` |
| **Owner** | HR → Recruitment |
| **Audience** | Recruiters; executive/analytics for HR leadership |
| **Purpose** | Pipeline velocity, offer status, source quality |
| **Type** | Operational / Executive / Analytics |

---

### 11. ESS My HR

| Field | Value |
|-------|-------|
| **Route** | `ess.dashboard` |
| **Owner** | Employee (personal) |
| **Audience** | Employees |
| **Purpose** | My leave, attendance, payslips shortcuts |
| **Type** | Personal |
| **Permission** | `ess.access` |

---

### 12. Analytics / Executive (target)

| Field | Value |
|-------|-------|
| **Route** | Future Analytics workspace home; today partial via Reports + exec project views |
| **Owner** | Analytics workspace |
| **Audience** | CEO, Org owner, Department heads |
| **Purpose** | Cross-module KPIs |
| **Type** | Executive |

---

### 13. Finance operational/analytics

| Field | Value |
|-------|-------|
| **Route** | `reports.finance` (+ invoice widgets) |
| **Owner** | Analytics (Finance) / future Finance workspace |
| **Audience** | Finance manager |
| **Purpose** | Outstanding AR, revenue |
| **Type** | Operational + Analytics |

---

### 14. Candidate Careers Dashboard

| Field | Value |
|-------|-------|
| **Route** | `careers.dashboard` |
| **Owner** | Careers product surface |
| **Audience** | External candidates |
| **Purpose** | Applications, offers, profile |
| **Type** | Personal (external) |

---

### 15. Platform Dashboard

| Field | Value |
|-------|-------|
| **Route** | `platform.dashboard` |
| **Owner** | Platform product |
| **Audience** | Platform administrators |
| **Purpose** | Tenant health, operator tasks |
| **Type** | Administration (platform) |

---

## Type definitions

| Type | Question it answers |
|------|---------------------|
| **Personal** | What do I need to do? |
| **Operational** | How is my team’s work going? |
| **Department** | How is my department performing? |
| **Executive** | How is the business/portfolio performing? |
| **Workspace** | How is this domain performing? |

---

## Ownership matrix (quick)

| Dashboard | Workspace | Primary persona |
|-----------|-----------|-----------------|
| Home | Home | All |
| CRM widgets | CRM | Sales |
| Projects dashboard | Projects | Project Manager |
| Projects executive | Projects / Analytics | CEO / Dept Head |
| Portfolio/Program | Projects | PMO |
| HR dashboard | HR | HR Manager |
| Manager dashboard | HR | Department Manager |
| Leave dashboard | HR | HR Manager |
| Recruitment dashboards | HR | Recruiter / HR Lead |
| ESS | HR (My HR) | Employee |
| Finance report | Analytics | Finance Manager |
| Careers | External | Candidate |
| Platform | Platform | Platform Admin |

---

## Design rules

1. **One primary dashboard per persona landing** — others linked, not competing in sidebar.  
2. **Do not put every dashboard in the main sidebar** — only Home + optional workspace home.  
3. **Entity dashboards** stay inside entity context.  
4. **Executive dashboards** live under Analytics or workspace “Executive” secondary nav.  
5. Widget modules in `config/dashboard.php` map to workspaces for future filtering.

---

## Gaps to close in Phase 14

- Explicit CRM workspace home dashboard  
- Analytics workspace overview composing existing reports  
- Reduce sidebar competition between Dashboard / HR Dashboard / Manager Dashboard / Recruitment Dashboard  
- Align support/workflow widget sections with real modules when Support ships  
