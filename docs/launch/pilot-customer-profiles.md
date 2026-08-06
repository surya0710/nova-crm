# Deliverable 1 — Pilot Customer Profiles

Representative pilot organizations created by `php artisan pilot:seed` ([PilotCustomerSeeder](../../database/seeders/PilotCustomerSeeder.php)).

Password for all seeded users: `password`

---

## Customer A — Apex Sales Partners

| Field | Value |
|-------|-------|
| Code | A |
| Slug | `apex-sales-partners` |
| Plan | starter |
| Industry | B2B Sales Agency |
| HQ | Mumbai, Maharashtra, India |
| Modules | CRM, Tasks |
| Owner | Ananya Rao — `owner@apexsales.test` |
| Role users | manager / hr / sales / employee `@apex-sales-partners.test` |
| Sample data | 6 leads, 4 customers, 8 employees, departments, branch |

**Licensing intent:** CRM-only starter — Projects, HRMS, Marketing, Analytics must remain hidden/blocked.

---

## Customer B — Meridian People Works

| Field | Value |
|-------|-------|
| Code | B |
| Slug | `meridian-people-works` |
| Plan | professional |
| Industry | Professional Services HR |
| HQ | Bengaluru, Karnataka, India |
| Modules | HRMS, Recruitment, Projects, Tasks |
| Owner | Meera Krishnan — `owner@meridianpeople.test` |
| Sample data | Employees, leave balances, 2 projects + milestones/tasks |

**Licensing intent:** HRMS + Projects — CRM / Marketing / Analytics not licensed.

---

## Customer C — Cascade Growth Labs

| Field | Value |
|-------|-------|
| Code | C |
| Slug | `cascade-growth-labs` |
| Plan | professional |
| Industry | Growth Marketing |
| HQ | Chennai, Tamil Nadu, India |
| Modules | CRM, Marketing, Analytics, Tasks |
| Owner | Karthik Iyer — `owner@cascadegrowth.test` |
| Sample data | Leads/customers, marketing provider + campaign |

**Licensing intent:** Growth stack — HRMS / Projects not licensed.

---

## Customer D — Harbor Delivery Collective

| Field | Value |
|-------|-------|
| Code | D |
| Slug | `harbor-delivery-collective` |
| Plan | professional |
| Industry | Delivery & Implementation |
| HQ | Pune, Maharashtra, India |
| Modules | Projects, HRMS, Recruitment, Marketing, Tasks |
| Owner | Priya Deshmukh — `owner@harbordelivery.test` |
| Sample data | Employees/leave, projects, marketing campaign |

**Licensing intent:** Delivery stack — CRM / Analytics not licensed.

---

## Customer E — Summit Enterprise Group

| Field | Value |
|-------|-------|
| Code | E |
| Slug | `summit-enterprise-group` |
| Plan | enterprise |
| Industry | Diversified Enterprise |
| HQ | New Delhi, Delhi, India |
| Modules | Full suite (CRM, Projects, HRMS, Recruitment, Marketing, Analytics, Finance, Support, Workflow, Tasks, Customer Portal, Inventory, Assets) |
| Owner | Rohan Malhotra — `owner@summitenterprise.test` |
| Sample data | All applicable module datasets above |

**Licensing intent:** Full Enterprise Suite — all workspaces available subject to RBAC.

---

## Shared structure per org

- Branch HQ
- Departments: Operations, Sales, HR, Delivery
- Designations aligned to departments
- Roles exercised: Organization Owner, Manager, HR, Sales Executive, Employee
- Branding stub in `settings.branding`
- Dashboard widgets provisioned when table exists

Always-on (non-licensable) modules remain enabled via `OrganizationUpgradeService::syncModuleAssignments`: `common`, `notifications`, `calendar`.
