# Deliverable 5 — Navigation Hierarchy

Target navigation model for Konnect Nex. Implementation deferred to Phase 14+.

---

## Layers

```
┌─────────────────────────────────────────────────────────────┐
│  Global chrome: Org · Workspace · Search · Help · Alerts · User │
├──────────────┬──────────────────────────────────────────────┤
│  Sidebar     │  Page canvas                                 │
│  - Workspace │  - Breadcrumbs                               │
│  - Primary   │  - Page title + primary actions              │
│  - Favorites │  - Context / secondary nav (tabs)            │
│  - Recents   │  - Content                                   │
│  - Admin     │  - Entity nav (when on a record)             │
└──────────────┴──────────────────────────────────────────────┘
```

---

## 1. Top-level navigation (global chrome)

| Control | Behavior |
|---------|----------|
| **Organization switcher** | Switch tenant; preserved across workspaces |
| **Workspace switcher** | Home, CRM, Projects, HR, Marketing, Operations, Analytics, Administration |
| **Global Search** | `⌘K` / `Ctrl+K` opens command/search; scoped toggle |
| **Help** | Knowledge Center |
| **Notifications** | Bell inbox |
| **User menu** | Profile, My preferences, Theme (future), Log out |

No module links in the top bar except Search/Help/Alerts.

---

## 2. Workspace navigation (sidebar primary)

Shown after workspace selection. Max **7–9** primary items before “More”.

### Home
- Home
- My Work (tasks assigned to me)
- Approvals (future / leave approvals shortcut)
- Recents

### CRM
- Leads
- Customers
- Opportunities
- Revenue ▾ (Quotations, Invoices, Payments)
- Catalog ▾ (Products) — or under More
- More ▾ (Imports, Assignment rules → settings)

### Projects
- Projects
- Portfolios
- Programs
- Resources
- Risks & Issues
- Reports
- More ▾ (Templates, Catalogs, Automation)

### HR
- People ▾ (Directory, Employees, Teams)
- Time ▾ (Attendance, Shifts)
- Leave
- Recruitment
- Performance
- Payroll
- More ▾ (Announcements, Exit, Calendar)

*If only `ess.access`:* show **My HR** tree instead of admin HR.

### Marketing
- Attribution
- Providers
- Campaigns (future)

### Operations
- Tasks
- Assignment inbox (future)
- Inventory (future)

### Analytics
- Overview
- Sales reports
- Delivery reports
- People reports
- Finance
- Audit Log

### Administration
- Users
- Roles & Permissions
- Billing
- Security / Audit
- Configuration Hub →

---

## 3. Context navigation (page secondary)

Horizontal tabs or local sidebar for multi-view modules.

Examples:

| Context | Tabs |
|---------|------|
| Opportunities | Board · List · Forecast (future) |
| Tasks | Board · List · Timeline · Calendar |
| Project | Overview · Tasks · Timeline · Progress · Risks · Budget · Files · Settings |
| Recruitment | Dashboard · Openings · Candidates · Interviews · Offers · Careers |
| Leave | My requests · Team queue · Balances · Policies (admin) |
| Employee | Profile · Documents · Attendance · Leave · Payroll · Performance |

---

## 4. Entity navigation

On record pages, provide:

1. **Identity header** — name, status, key attributes, primary CTA
2. **Related tabs** — notes, activity, files, related records
3. **Overflow menu** — archive, delete, transfer ownership, audit

Cross-module links use chips (“Open Customer”, “View Project”) — do not nest foreign module sidebars.

---

## 5. Settings navigation

All configuration under **Configuration Hub** with groups:

1. Organization  
2. Users & Security (deep-link to Administration)  
3. CRM  
4. Projects  
5. HR  
6. Marketing  
7. Automation  
8. Notifications  
9. Custom Fields (Metadata)  
10. Integrations  
11. Billing  

See [settings-architecture.md](./settings-architecture.md).

---

## 6. Breadcrumb strategy

Pattern:

`Workspace > Primary nav item > Optional section > Record name`

Examples:

- `CRM > Leads > Acme Inquiry`
- `Projects > Projects > Website Redesign > Risks`
- `HR > Recruitment > Candidates > Jane Doe`
- `Administration > Configuration > Leave Policies`

Rules:

- Breadcrumbs are links except the current page.
- Record name truncates; full name in title.
- Do not include ephemeral filters in breadcrumbs.
- External Careers/Platform use their own breadcrumb roots.

---

## 7. Secondary navigation & overflow

| Pattern | Use |
|---------|-----|
| **Tabs** | Alternate views of same entity set |
| **Segmented control** | 2–4 mutually exclusive views |
| **Overflow (⋯)** | Destructive/rare actions |
| **More in sidebar** | Items below primary budget |
| **In-page sections** | Settings hub cards |

---

## 8. Quick access

| Mechanism | Content |
|-----------|---------|
| Favorites | User-pinned pages |
| Pinned | Org-recommended for role (admin-configurable later) |
| Recents | Last 10 entities/pages |
| Quick actions | Workspace-specific create shortcuts |
| Command palette | Navigate + create + search (Phase 14 foundation) |

---

## Complete navigation tree (target)

```
[Org Switcher]
[Workspace Switcher]
│
├─ Home
│  ├─ Home
│  ├─ My Work
│  ├─ Approvals
│  └─ Recents
│
├─ CRM
│  ├─ Leads
│  ├─ Customers
│  ├─ Opportunities
│  ├─ Revenue
│  │  ├─ Quotations
│  │  ├─ Invoices
│  │  └─ Payments
│  ├─ Catalog
│  │  └─ Products
│  └─ More
│     ├─ Import Leads
│     └─ Assignment Rules → Config
│
├─ Projects
│  ├─ Projects
│  ├─ Portfolios
│  ├─ Programs
│  ├─ Resources
│  ├─ Risks & Issues
│  ├─ Reports
│  └─ More
│     ├─ Templates
│     ├─ Catalogs
│     └─ Automation
│
├─ HR
│  ├─ People
│  │  ├─ Directory
│  │  ├─ Employees
│  │  └─ Teams
│  ├─ Time
│  │  ├─ Attendance
│  │  └─ Shifts
│  ├─ Leave
│  ├─ Recruitment
│  │  ├─ Dashboard
│  │  ├─ Openings
│  │  ├─ Candidates
│  │  ├─ Interviews
│  │  ├─ Offers
│  │  └─ Careers Settings
│  ├─ Performance
│  ├─ Payroll
│  └─ More
│     ├─ Announcements
│     ├─ Exit Processes
│     └─ Calendar
│  └─ (ESS) My HR
│     ├─ Dashboard
│     ├─ Profile
│     ├─ Documents
│     ├─ Attendance
│     ├─ Leave
│     └─ Payroll
│
├─ Marketing
│  ├─ Attribution
│  └─ Providers
│
├─ Operations
│  └─ Tasks
│
├─ Analytics
│  ├─ Overview
│  ├─ Sales
│  ├─ Delivery
│  ├─ People
│  ├─ Finance
│  └─ Audit Log
│
└─ Administration
   ├─ Users
   ├─ Roles & Permissions
   ├─ Billing
   ├─ Security
   └─ Configuration Hub
      ├─ Organization
      ├─ Structure
      ├─ CRM
      ├─ Projects
      ├─ HR
      ├─ Marketing
      ├─ Automation
      ├─ Notifications
      ├─ Custom Fields
      └─ Integrations
```

---

## Mapping from current sidebar

| Current section | Target |
|-----------------|--------|
| Main → Dashboard | Home workspace |
| CRM (incl. Tasks, Projects, Resources) | Split → CRM / Operations / Projects |
| HR flat list | HR grouped nav |
| Self-Service | HR ESS mode |
| Analytics | Analytics workspace |
| Settings mix | Administration + Configuration Hub + Help in chrome |
