# Deliverable 7 — Settings Architecture

Centralized **Configuration Hub** for Konnect Nex. Operational screens stay in workspaces; configuration converges here.

---

## Principles

1. **Configure vs operate** — Policies and catalogs live in the Hub; day-to-day records stay in workspaces.
2. **One home per setting** — No duplicate sidebar entries for the same config page.
3. **Permission-gated cards** — Hub shows only allowed sections.
4. **Deep links** — Modules may link “Manage X settings” → Hub section.
5. **Preserve routes** — Phase 14 can re-skin without breaking URLs initially.

---

## Current state audit

Source: `config/organization_settings.php` + `App\Services\Configuration\ConfigurationRegistry`.

The hub is a **presentation catalog**. Settings still live on existing routes/controllers (`organization.settings.*`, Administration, HRMS catalogs, CRM screens). There is no per-module settings app.

| Setting / page | Owning hub module | License | Notes |
|----------------|-------------------|---------|-------|
| Organization Profile / Email | Organization | — | `organization.edit` |
| Branding / Modules / Subscription / Billing | Organization | — | Administration + settings aliases |
| Users | Organization | — | Deep link to Team |
| Lead / Customer / Pipeline | CRM | `crm` | Catalog deep links until dedicated config pages exist |
| Assignment rules | CRM → Sales | `crm` | `organization.settings.assignments` |
| Tax / GST, Products, Price Lists, Quotations, Invoices, Payments, Automation | Commercial | `crm` | GST state on org profile; automation on `organizations.settings` |
| Employee, Branches, Departments, Designations, Working Days, Shifts, Leave, Holidays, Attendance, WFH, Payroll | HRMS | `hrms` | Hidden on starter / when HRMS is disabled |
| Careers + Candidate Portal | HRMS | `recruitment` | Section-level license override |
| Project + task catalogs | Projects | `projects` | Previously missing from hub |
| Marketing providers | Marketing | `marketing` | Previously missing from hub |
| Security / Access Control / Audit | Security | — | Also linked from Administration nav |
| Notifications, Workflows, Custom Fields, Integrations, API, Developer | Platform | Workflows require `workflow` | Dashboard card removed (not a setting) |

### Duplicates / overlaps (presentation only)

- Working Days and Business Hours previously both pointed at `working-days.edit` — merged.
- Reporting Structure duplicated Departments — dropped from the hub.
- Access Control, Integrations, and API remain in Administration nav **and** the hub (deep links, not second implementations).
- Commercial Automation previously sat under a generic “CRM Configuration” group and was shown even when CRM was not licensed.

### Settings previously shown without the related module

Before the registry, the hub filtered **permissions only**. Starter (CRM-only) organizations still saw HR Configuration, Project Defaults, and Commercial Automation cards. Those groups are now gated by `ModuleSubscriptionService::moduleAllowed()`.

---

## Configuration Hub structure

**Entry:** Administration → Configuration · or gear icon · route evolves from `organization.settings.hub`.

### Group A — Organization

| Section | Contents | Permission |
|---------|----------|------------|
| Profile | Name, legal, timezone, currency, locale | `settings.manage` |
| Branding | Logo, colors | `settings.manage` |
| Email | From addresses, mail config | `settings.manage` |
| Working calendar | Working days (org-wide) | HR config perms |

### Group B — Users & Security

| Section | Contents | Permission |
|---------|----------|------------|
| Users | Deep link to Team | `users.view` |
| Roles & Permissions | Deep link to RBAC | `rbac.view` |
| Audit Log | Deep link | `audit.view` |
| Security policies | Session/password (future) | Admin |

*Billing stays under Administration primary nav but also linked here.*

### Group C — CRM

| Section | Contents | Permission |
|---------|----------|------------|
| Assignment rules | Pools, routing | assignments / settings |
| Pipeline stages | Opportunity stages (when configurable) | opportunities manage |
| Lead sources / statuses | Catalogs | leads manage |
| Numbering & document templates | Quote/invoice templates (future) | finance/settings |
| Products catalog | Optional deep link | `products.view` |

### Group D — Projects

| Section | Contents | Permission |
|---------|----------|------------|
| Categories / Types / Statuses / Lifecycle | Project catalogs | projects manage |
| Task statuses / priorities | Task catalogs | tasks manage |
| Templates | Project templates | templates perms |
| Default automation | Link to project automation | workflows |

### Group E — HRMS

| Section | Contents | Permission |
|---------|----------|------------|
| Structure | Branches, Departments, Designations | structure perms |
| Shifts | Shift definitions | attendance |
| Holidays | Holiday calendar | leave |
| Leave types & policies | Types, policies, approvers | leave manage |
| Attendance rules | Rules | attendance manage |
| Payroll setup | Statutory, salary structures | payroll manage |
| Performance setup | Cycle templates | performance manage |

### Group F — Marketing

| Section | Contents | Permission |
|---------|----------|------------|
| Attribution defaults | Models/settings | marketing |
| Careers site | Deep link to recruitment careers settings | recruitment manage |

### Group G — Automation

| Section | Contents | Permission |
|---------|----------|------------|
| Workflows | Global workflows | `workflows.view` |
| Executions | Run history | workflows |
| Assignment engine | CRM assignments | assignments |

### Group H — Notifications

| Section | Contents | Permission |
|---------|----------|------------|
| Organization defaults | Channels, templates | `settings.manage` |
| Module alerts | Which events notify | settings |

Personal preferences → **User menu**, not Hub.

### Group I — Metadata (Custom Fields)

| Section | Contents | Permission |
|---------|----------|------------|
| Field definitions | Custom fields | `metadata.view` |
| Layouts / blueprints | Layout activation | metadata manage |
| Field permissions | Field-level ACL | metadata manage |

**UI label:** Custom Fields · **Advanced docs:** Metadata.

### Group J — Integrations

| Section | Contents | Permission |
|---------|----------|------------|
| Connected apps | Integrations index | `integrations.view` |
| Marketing providers | OAuth providers | integrations/marketing |
| API tokens | Developer tokens | `api.tokens` |
| Webhooks | Inbound/outbound (document) | integrations |

### Group K — Billing

| Section | Contents | Permission |
|---------|----------|------------|
| Subscription | Plan, modules | `settings.manage` |
| Billing | Invoices/payment method | `settings.manage` |

---

## Hub IA wireframe

```
Configuration Hub
├── Organization
├── CRM
│   ├── Lead Settings
│   ├── Customer Settings
│   ├── Pipeline
│   └── Sales
├── Commercial
│   ├── Tax / GST
│   ├── Products
│   ├── Quotations
│   ├── Invoices
│   ├── Payments
│   └── Automation
├── HRMS
│   ├── Employee
│   ├── Branches
│   ├── Shifts
│   ├── Leave
│   ├── Holidays
│   ├── Attendance
│   ├── WFH
│   ├── Payroll
│   └── Recruitment
├── Projects
├── Marketing
├── Security
└── Platform
```

Each module is a card grid of sections, not a second mega-sidebar. Modules with no visible sections are omitted.

---

## What leaves the main sidebar

Move to Hub or chrome only:

- Workflows  
- Metadata Fields  
- Integrations  
- API Tokens  
- Organization Settings (becomes Hub entry under Administration)  
- Knowledge Center → Help  
- Profile → User menu  

Keep in Administration nav: Users, Roles, Billing, Configuration.

---

## Future module: Assets

`organization_settings.future_modules.assets` remains hidden until production-ready; then add **HR → Assets** ops + **Configuration → Assets** policies.

---

## Migration guidance (Phase 14)

1. `config/organization_settings.php` is the module-aware registry; `ConfigurationRegistry` filters by plan, enabled modules, and permissions.
2. Missing section entries added (workflows, custom fields, assignments, project catalogs, payroll, recruitment).
3. Remove remaining duplicate sidebar links in a later pass; hub cards are deep links.
4. Keep redirects for HR structure URLs (`organization.settings.branches` → `/hrms/branches`, etc.).
5. Dashboard card removed from the settings catalog.
6. Hub polish (search, empty states, breadcrumbs, recently used) stays on this catalog — see [configuration-registry.md](./configuration-registry.md).
