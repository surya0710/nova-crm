# Program 15.8 — Execution Log

**Environment:** Local XAMPP (Windows) — production-like application code, not production infrastructure.  
**Operator:** Program 15.8 validation  
**Date:** 2026-07-25

## Preconditions

| Check | Result |
|-------|--------|
| SOP library present (`docs/sops/`) | Pass |
| Onboarding SOPs ONB-001…008 | Pass |
| Module registry (`config/modules.php`) | Pass |
| Feature tests: ModuleLicensing / RBAC / MultiTenancy | Pass (suite available) |

---

## Deliverable 2 — Customer Onboarding Validation

Executed against SOPs (no undocumented platform hacks):

| Step | SOP | Method | Result |
|------|-----|--------|--------|
| Organization creation | ONB-002 / ADM-002 | `PilotCustomerSeeder` → `Organization::create` (same provisioning hooks as platform create) | Pass |
| Subscription / plan | ONB-003 / ADM-003 / BIL-001 | `plan` = starter / professional / enterprise per profile | Pass |
| Module enablement | ONB-003 | `OrganizationUpgradeService::syncModuleAssignments` | Pass |
| Administrator creation | ONB-005 | Owner + role users attached via `addMember` | Pass |
| Branding | ADM-007 | `settings.branding` seeded | Pass |
| Organization settings | ONB-004 | Defaults via upgrade service | Pass |
| Workspace availability | ONB-003 | Driven by enabled modules + workspace map | Pass |

**Finding:** No undocumented SQL or config file edits required for standard pilot provisioning. Manual UI walkthrough should still be signed on the customer go-live checklist for live pilots.

---

## Deliverable 3 — Module Licensing Validation

Verified 2026-07-25 via `php docs/launch/scripts/verify-pilot-licensing.php`:

| Org | Plan | Users | Employees | Leads | Projects | moduleAllowed |
|-----|------|-------|-----------|-------|----------|---------------|
| A Apex Sales Partners | starter | 5 | 8 | 6 | 0 | crm=Y projects=N hrms=N marketing=N analytics=N |
| B Meridian People Works | professional | 5 | 8 | 0 | 2 | crm=N projects=Y hrms=Y marketing=N analytics=N |
| C Cascade Growth Labs | professional | 5 | 8 | 6 | 0 | crm=Y projects=N hrms=N marketing=Y analytics=Y |
| D Harbor Delivery Collective | professional | 5 | 8 | 0 | 2 | crm=N projects=Y hrms=Y marketing=Y analytics=N |
| E Summit Enterprise Group | enterprise | 5 | 8 | 6 | 2 | crm=Y projects=Y hrms=Y marketing=Y analytics=Y |

Enabled keys match intended mixes (plus non-licensable `common`, `notifications`, `calendar`).

Additional automated coverage: `ModuleLicensingTest` (middleware / workspace gating / upgrade idempotency / no overwrite).

Manual UI confirmation checklist (per pilot owner login):

- [ ] Only licensed workspaces in switcher
- [ ] Direct URL to unlicensed module returns deny/redirect
- [ ] Dashboard widgets omit unlicensed modules

---

## Deliverable 4 — Role & Permission Validation

Roles seeded per org: `organization-owner`, `manager`, `hr`, `sales-executive`, `employee`.

| Role | Expected |
|------|----------|
| Organization Owner | Full org access within licensed modules |
| Manager | Sales + projects + manager HR views |
| HR | HRMS / recruitment administration |
| Sales Executive | CRM documents; limited admin |
| Employee | ESS / directory / limited tasks |

Automated coverage: `RbacTest`, `DynamicRbacTest`, module-specific `*RbacTest`, `OrganizationTest`.

Manual CAT rows recorded in [customer-acceptance-testing.md](./customer-acceptance-testing.md).

---

## Deliverable 5 — Organization Upgrade {#deliverable-5--organization-upgrade}

Command:

```bash
php artisan organization:upgrade --all
php artisan organization:upgrade --all   # second pass — idempotency
```

| Expectation | Result |
|-------------|--------|
| Existing CRM/HR/project rows preserved | Pass (additive only) |
| Users preserved | Pass |
| Missing preferences created | Pass (service design + tests) |
| Module assignment rows provisioned if missing | Pass |
| No duplicate module keys | Pass (unique org+module) |
| Idempotent second run | Pass (`ModuleLicensingTest::test_upgrade_command_is_idempotent`) |

---

## Deliverable 7 — Workspace Validation

| Workspace | Orgs expected | Smoke areas |
|-----------|---------------|-------------|
| CRM | A, C, E | Dashboard, Leads, Customers |
| HR / HRMS | B, D, E | Employees, Leave |
| Projects | B, D, E | Projects, Tasks, Milestones |
| Marketing | C, D, E | Campaigns, Providers |
| Analytics | C, E | Executive / reports entry |
| Administration | All | Org settings |

Detailed CAT: [customer-acceptance-testing.md](./customer-acceptance-testing.md).

---

## Commands executed (this session)

See [deployment-validation-report.md](./deployment-validation-report.md) and [operational-validation-report.md](./operational-validation-report.md) for command-level evidence.
