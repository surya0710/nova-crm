# Deliverable 1 — Workspace Home Blueprints

Landing-page specification for every NovaCRM workspace. Implementation deferred to Phase 14+. Aligns with [workspaces.md](./workspaces.md) and [dashboard-ownership.md](./dashboard-ownership.md).

---

## Shared landing anatomy

Every workspace home uses the same regions (progressive disclosure by role):

```
┌──────────────────────────────────────────────────────────────┐
│ Header: Workspace name · Scope chips · Customize             │
├──────────────┬───────────────────────────────────────────────┤
│ Attention    │  KPI strip (3–6 metrics)                      │
│ (priority)   ├───────────────────────────────────────────────┤
│              │  Primary widgets (2×2 or 3-col grid)          │
├──────────────┼───────────────────────────────────────────────┤
│ Quick        │  Activity / Recents                           │
│ Actions      │  Pinned · Favorites                           │
└──────────────┴───────────────────────────────────────────────┘
│ Notifications preview (optional strip)                       │
└──────────────────────────────────────────────────────────────┘
```

**Immediate clarity checklist (every home):**

1. Where am I? → Workspace title + accent  
2. What needs attention? → Attention rail / badge counts  
3. What next? → Quick Actions  
4. How elsewhere? → Primary nav + search scope label  

---

## 1. Home

| Field | Spec |
|-------|------|
| **Purpose** | Personal command center across the platform |
| **Primary audience** | All authenticated members |
| **Landing widgets** | Welcome · Needs Attention · My Tasks · Notifications · Calendar · Role-relevant “My …” (Leads / Approvals / Projects) |
| **KPIs** | Open tasks assigned to me · Unread notifications · Pending approvals · Today’s calendar items |
| **Quick actions** | Permission-based: New Task, New Lead, Apply Leave, Mark Attendance |
| **Recent activity** | Personal feed: my assignments, mentions, completed items |
| **Pinned content** | User favorites + role pins |
| **Notifications** | Top 5 unread; link to Inbox |
| **Search scope** | Everywhere (default) |
| **Navigation** | Home · My Work · Approvals · Recents |

---

## 2. CRM

| Field | Spec |
|-------|------|
| **Purpose** | Win and collect revenue today |
| **Primary audience** | Sales executive, Sales manager, Finance (AR) |
| **Landing widgets** | My Leads · Pipeline summary · Quotations due · Outstanding invoices · Won this period |
| **KPIs** | Open leads · Pipeline value · Quotes awaiting response · Overdue invoices · Payments this week |
| **Quick actions** | Create Lead · Create Opportunity · New Quotation · Record Payment |
| **Recent activity** | Lead/Opportunity updates, quote sends, payment recorded |
| **Pinned content** | Saved pipeline filters, key customers |
| **Notifications** | Assignment, quote viewed/accepted, payment received |
| **Search scope** | CRM entities |
| **Navigation** | Leads · Customers · Opportunities · Revenue · Catalog · More |

**Manager variant:** Team pipeline / assignment queue widgets replace personal “My Leads” emphasis.

---

## 3. Projects

| Field | Spec |
|-------|------|
| **Purpose** | Deliver work on time and on budget |
| **Primary audience** | Project manager, Team lead, PMO |
| **Landing widgets** | My Projects · At-risk projects · Upcoming milestones · Resource load · Open risks/issues |
| **KPIs** | Active projects · Red/amber health · Overdue tasks · Utilization % · Open critical risks |
| **Quick actions** | New Project · New Task · Log Progress · Allocate Resource · Log Risk |
| **Recent activity** | Progress updates, mentions, risk escalations |
| **Pinned content** | Watched projects, portfolio shortcuts |
| **Notifications** | Mentions, task assigns, health threshold breaches |
| **Search scope** | Projects, Tasks, Portfolios, Programs, Risks, Issues |
| **Navigation** | Projects · Portfolios · Programs · Resources · Risks & Issues · Reports |

**PMO / Executive variant:** Link to Projects Executive / Portfolio Executive dashboards from header.

---

## 4. HR

| Field | Spec |
|-------|------|
| **Purpose** | Hire, support, and pay people |
| **Primary audience** | HR manager, Recruiter, Department manager |
| **Landing widgets** | Headcount pulse · Leave pending approval · Attendance exceptions · Recruitment pipeline · Payroll run status (period) |
| **KPIs** | Active employees · Pending leave · Open openings · Candidates in interview · Payroll cutoff countdown |
| **Quick actions** | Add Employee · Approve Leave · Post Opening · New Announcement |
| **Recent activity** | Joiners/leavers, leave decisions, offer updates |
| **Pinned content** | Approval queue, careers settings (admin) |
| **Notifications** | Leave requests, offer approvals, document expiry |
| **Search scope** | Employees, Candidates, Openings, Leave (when search extended) |
| **Navigation** | People · Time · Leave · Recruitment · Performance · Payroll · More |

**ESS / My HR variant (employees):** My Leave balance · Attendance today · Payslips · Profile completeness — no admin KPIs.

**Manager variant:** Team leave queue · Team attendance · Direct reports.

---

## 5. Marketing

| Field | Spec |
|-------|------|
| **Purpose** | Acquire and attribute demand |
| **Primary audience** | Marketing ops, Growth |
| **Landing widgets** | Lead sources · Attribution summary · Provider health · Campaign performance (future) |
| **KPIs** | New attributed leads · Cost per lead (future) · Provider sync status · Career applications from campaigns |
| **Quick actions** | Connect Provider · View Lead Sources · Launch Campaign (future) |
| **Recent activity** | Provider sync events, attribution touches |
| **Pinned content** | Top sources |
| **Notifications** | Provider disconnect, sync failure |
| **Search scope** | Marketing + CRM leads with source |
| **Navigation** | Attribution · Providers · Campaigns (future) |

---

## 6. Operations

| Field | Spec |
|-------|------|
| **Purpose** | Clear cross-functional daily work |
| **Primary audience** | Operations manager, multi-module users |
| **Landing widgets** | My / Team Tasks · Overdue · Approvals inbox · Assignment failures · Workflow failures |
| **KPIs** | Open tasks · Overdue · Pending approvals · Failed automations |
| **Quick actions** | New Task · Open Approvals · View Workflows |
| **Recent activity** | Task completes, assignment events |
| **Pinned content** | Saved task boards |
| **Notifications** | Assignments, approval requests, workflow errors |
| **Search scope** | Tasks + assignment-related |
| **Navigation** | Tasks · Approvals · Assignments |

---

## 7. Analytics

| Field | Spec |
|-------|------|
| **Purpose** | Understand business performance |
| **Primary audience** | CEO, Department head, Finance manager, Org owner |
| **Landing widgets** | Sales overview · Delivery health · People snapshot · Finance AR · Exception list |
| **KPIs** | Pipeline vs target · Project health rollup · Attrition/headcount · Outstanding AR · Audit anomalies (count) |
| **Quick actions** | Open Sales report · Open Finance · Export · Open Executive Projects |
| **Recent activity** | Report generations, threshold alerts |
| **Pinned content** | Saved reports |
| **Notifications** | Threshold breaches, scheduled report ready |
| **Search scope** | Report titles + deep-link entities |
| **Navigation** | Overview · Sales · Delivery · People · Finance · Audit |

---

## 8. Administration

| Field | Spec |
|-------|------|
| **Purpose** | Keep the tenant secure and correctly configured |
| **Primary audience** | Organization administrator, Owner |
| **Landing widgets** | Users & seats · Roles summary · Integration health · Failed workflows · Recent audit events · Billing/plan |
| **KPIs** | Active users · Invites pending · Integrations degraded · Open audit flags · Plan usage |
| **Quick actions** | Invite User · Create Role · Open Configuration Hub · Rotate API token |
| **Recent activity** | Permission changes, user invites, config updates |
| **Pinned content** | Configuration Hub sections |
| **Notifications** | Security alerts, billing, integration failures |
| **Search scope** | Users, Roles, Settings sections |
| **Navigation** | Users · Roles · Billing · Security · Configuration |

---

## Future workspace homes (placeholders)

### Finance

| Field | Spec |
|-------|------|
| **Purpose** | Beyond AR — expenses, GL, payouts |
| **Audience** | Finance manager |
| **Landing** | Cash position · Expense approvals · Period close checklist |
| **Show when** | Finance module GA |

### Support

| Field | Spec |
|-------|------|
| **Purpose** | Resolve customer issues |
| **Audience** | Support agent, Support manager |
| **Landing** | My tickets · SLA breaches · CSAT |
| **Show when** | Support module GA |

### Assets

| Field | Spec |
|-------|------|
| **Purpose** | Asset lifecycle |
| **Audience** | IT / HR assets ops |
| **Landing** | Assigned to me · Expiring warranties · Unassigned stock |
| **Show when** | Assets production-ready |

---

## Layout rules (all homes)

| Rule | Value |
|------|-------|
| Above-the-fold | Attention + KPIs + ≤ 4 primary widgets |
| Max widgets default | 8 visible; more via Customize |
| Density | Comfortable default; Compact preference |
| Empty home | See [empty-states.md](./empty-states.md) |
| Permission | Hide widgets/actions without access — never show locked teasers that 403 |

---

## Mapping to existing routes (transition)

| Workspace home | Near-term host |
|----------------|----------------|
| Home | `dashboard` |
| CRM | New workspace home (or filtered Home sections) |
| Projects | `projects.home` (+ executive / portfolio executive from header actions) |
| HR | `hrms.dashboard` / manager / ESS |
| Marketing | New MVP home |
| Operations | Tasks-centric home |
| Analytics | New overview wrapping `reports.*` |
| Administration | New admin health home |
