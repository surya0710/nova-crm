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

Source: `config/organization_settings.php` + sidebar Settings.

| Setting / page | Current location | Issue |
|----------------|------------------|-------|
| Organization Profile | Hub + `organization.edit` | OK |
| Branding | Hub tab | OK |
| Subscription / Billing | Hub | OK |
| Branches / Departments / Designations | Hub → redirects to HRMS | Split mental model |
| Working Days, Shifts, Holidays, Leave Types/Policies/Approvers, Attendance Rules | Hub / redirects | OK direction |
| Access Control | Hub + sidebar | Duplicate entry |
| Dashboard link | Hub (optional) | Misplaced (not a setting) |
| Notifications (org) | Hub | OK |
| Email | Org edit tab | OK |
| Integrations | Hub + sidebar | Duplicate |
| API Tokens | Hub + sidebar | Duplicate |
| Workflows | Sidebar only | Missing from Hub |
| Metadata Fields | Sidebar only | Missing from Hub |
| Assignment rules | Orphan routes | Missing from Hub |
| Project catalogs / task catalogs | Orphan / module-local | Missing from Hub |
| Recruitment careers/portal settings | Inside Recruitment | Acceptable ops-adjacent; also link from Hub |
| Payroll / Performance config | Inside modules | Need Hub cards |
| User notification preferences | Hidden routes | Belong under User menu |
| Knowledge Center | Sidebar Settings | Not a setting → Help |
| Profile | Sidebar Settings | Not org setting → User menu |
| Team (Users) | Sidebar | Administration, not Hub card only |

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
Configuration
├── Organization
├── Users & Security
├── CRM
├── Projects
├── HR
├── Marketing
├── Automation
├── Notifications
├── Custom Fields
├── Integrations
└── Billing
```

Each group page = card grid (current hub pattern), not a second mega-sidebar.

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

1. Extend `config/organization_settings.php` groups to match A–K.  
2. Add missing section entries (workflows, metadata, assignments, project catalogs).  
3. Remove duplicate sidebar links.  
4. Keep redirects for HR structure URLs.  
5. Remove Dashboard card from settings catalog.
