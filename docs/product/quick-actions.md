# Deliverable 4 — Quick Actions Framework

Universal Quick Actions for NovaCRM: the fastest path to high-frequency creates and decisions.

---

## Purpose

Reduce time-to-action. Primary work starts in **one click** from a workspace home or the global palette.

---

## Locations

| Location | Behavior |
|----------|----------|
| **Workspace home** | Horizontal action bar under header (primary) |
| **Personal Home** | Role-filtered global actions |
| **Command palette** | `Ctrl/⌘K` → Actions mode |
| **Dashboard widget** | Action-type widgets (e.g. Mark Attendance) |
| **Entity header** | Context actions (Convert, Create Quote) — related but not “Quick Actions bar” |
| **Mobile** | FAB or top sheet listing same items |

Do not scatter different create buttons with different labels for the same intent.

---

## Action anatomy

```
[ Icon ] Label
```

| Field | Rule |
|-------|------|
| **ID** | Stable slug `qa.crm.create_lead` |
| **Label** | Verb + noun from glossary (“Create Lead”) |
| **Icon** | One icon set; recognizable at 20px |
| **Permission** | Required permission(s) |
| **Workspace** | Primary workspace affinity |
| **Target** | Route or modal |
| **Risk** | `safe` · `confirm` · `destructive` |
| **Availability** | Plan + module enablement |

---

## Grouping

Display order on a bar:

1. **Create** (records)  
2. **Decide** (approvals)  
3. **Log** (attendance, progress, payment)  
4. **Admin** (invite, connect) — only in Admin/Home for admins  

Max **5** visible actions; overflow **More actions**.

Group headings in palette and More menus — not on the compact bar.

---

## Catalog by workspace

### Home (role-filtered union)

| Action | Permission hint |
|--------|-----------------|
| Create Task | `tasks.create` |
| Create Lead | `leads.create` |
| Apply Leave | `ess.access` / leave create |
| Mark Attendance | `ess.access` |
| Approve Leave | `leave.approve` |

### CRM

| Action | Permission |
|--------|------------|
| Create Lead | `leads.create` |
| Create Opportunity | `opportunities.create` |
| New Quotation | `quotations.create` |
| New Invoice | `invoices.create` |
| Record Payment | `payments.create` |
| Import Leads | `leads` import perm |

### Projects

| Action | Permission |
|--------|------------|
| Create Project | `projects.create` |
| Create Task | `tasks.create` |
| Log Progress | `projects.progress.create` |
| Allocate Resource | `resources.allocate` |
| Log Risk | `projects.risks.create` (or equiv.) |
| Log Issue | `projects.issues.create` |

### HR

| Action | Permission |
|--------|------------|
| Add Employee | employees create |
| Approve Leave | `leave.approve` |
| Post Opening | recruitment create |
| New Announcement | `announcements.manage` |
| Run Payroll (period) | `payroll` manage |

### Marketing

| Action | Permission |
|--------|------------|
| Connect Provider | integrations/marketing |
| View Lead Sources | leads/marketing view |
| Launch Campaign | future |

### Operations

| Action | Permission |
|--------|------------|
| Create Task | `tasks.create` |
| Open Approvals | leave/workflow approve |
| View Failed Workflows | `workflows.view` |

### Analytics

| Action | Permission |
|--------|------------|
| Open Finance Report | `finance.view` / reports |
| Export Outstanding | export perm |
| Open Executive Projects | `projects.executive.view` |

### Administration

| Action | Permission |
|--------|------------|
| Invite User | `users.create` / manage |
| Create Role | `rbac.roles.create` |
| Open Configuration | `settings.manage` |
| Create API Token | `api.tokens` |

---

## Role awareness

| Persona | Sees |
|---------|------|
| Sales Executive | CRM creates |
| Sales Manager | CRM + Import + team-oriented |
| Project Manager | Projects set |
| HR Manager | HR set |
| Recruiter | Post Opening, Candidate shortcuts |
| Employee | Apply Leave, Mark Attendance only |
| Admin | Admin set + whatever modules they hold |
| CEO | Analytics opens; few creates |

If a user has multiple roles, union actions then sort by **frequency score** (personal) with workspace affinity boost.

---

## Icons

- Use existing Heroicon-style set for consistency with `config/dashboard.php`  
- One metaphor per action; do not reuse Create icon for Approve  
- Color: inherit workspace accent; destructive = danger token only for destructive actions  

---

## Interaction

| Risk | Behavior |
|------|----------|
| `safe` | Navigate or open create form/modal |
| `confirm` | Soft confirm (Approve Leave) |
| `destructive` | Modal confirm |

After success: toast + optional “View record” + refresh home widgets.

Keyboard: palette selection; bar actions are tab stops.

---

## Permissions & empty

- Hide unauthorized actions (preferred over disabled).  
- If zero actions available: hide the bar (do not show empty shell).  
- Plan-gated: hide; Admins may see upgrade hint in Configuration only.

---

## Relationship to existing system

Today: `DashboardQuickAction` / `OrganizationQuickAction` / `QuickActionController`.

Phase 14: map catalog IDs → existing quick-action records; extend with workspace affinity metadata.

---

## Anti-patterns

- Different labels for same action across pages  
- More than 5 primary buttons  
- Quick Actions that deep-link to unrelated workspaces without context chip  
- Admin-only actions on Employee Home  
