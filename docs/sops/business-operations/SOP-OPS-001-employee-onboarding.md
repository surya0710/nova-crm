# SOP-OPS-001 — Employee Onboarding

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OPS-001 |
| **Title** | Employee Onboarding |
| **Version** | 1.0 |
| **Effective Date** | 2026-08-06 |
| **Department** | Business Operations (HR) |
| **Owner** | HR Administrator |
| **Reviewer** | Payroll Officer |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Provision a new hire in Konnect Nex so attendance, leave, payroll, tax, ESS, and documents work without manual database changes.

## Scope

- **In scope:** Employee record, org structure links, user account, salary/statutory setup, ESS access, document checklist.
- **Out of scope:** Recruitment offer acceptance (SOP-OPS-005), payroll run (SOP-OPS-003).

## Preconditions

- [ ] Organization provisioned and HRMS module licensed
- [ ] Branches, departments, designations, and shifts configured
- [ ] Salary structures / components published for the hire’s grade
- [ ] Actor has employee create / manage permissions (dynamic RBAC)

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| HR → Employees | Employee create/manage | Tenant permission, not role name |
| Administration → Users (if separate invite) | User invite | Optional if auto-provisioned |
| ESS | Employee self-service | After user linked |

## Step-by-step Procedure

### 1. Create employee master

1. Open HR → Employees → Create.
2. Enter legal name, employee code, join date, employment type, and work email.
3. Assign branch, department, designation, reporting manager, and default shift.
4. Save and confirm the employee appears only in the current organization.

### 2. Link login (ESS)

1. Provision or invite the user account for the employee (bulk or single provision job if used).
2. Confirm the user belongs to the same organization membership.
3. Assign an ESS-capable role via **permissions** (never hard-code role names in process notes beyond template names).

### 3. Compensation & statutory

1. Assign salary structure / components effective from join date (or payroll start).
2. Complete statutory profile (PAN, PF/ESI/PT as applicable).
3. If mid-year hire, ensure income tax financial year is active and regime selection is available.

### 4. Documents & acknowledgements

1. Request required documents via HR documents flow.
2. Verify uploads; reject incomplete packages with reason.
3. Confirm notifications/queue delivered request emails if mail is enabled.

### 5. Smoke validation

1. Employee can open ESS home.
2. Attendance punch/day view available for join date onward.
3. Leave balances initialized per policy (or documented as zero until accrual job runs).

## Validation Checklist

- [ ] Employee visible only in hiring org (no cross-tenant leak)
- [ ] User ↔ employee link present
- [ ] Salary assignment effective date correct
- [ ] Statutory fields required for payroll present
- [ ] ESS login succeeds
- [ ] Audit log shows create / assignment events

## Failure Handling

| Symptom | Action |
|---------|--------|
| User provision job stuck | Check queue worker / `php artisan queue:failed`; see SOP-MON-002 |
| Missing employee on ESS | Confirm link + membership; see `MissingEmployeeRecordException` handling |
| Payroll excludes hire | Check salary assignment dates vs payroll period |

## Related SOPs / Docs

- [SOP-ONB-005 — User Provisioning](../onboarding/SOP-ONB-005-user-provisioning.md)
- [SOP-OPS-003 — Payroll Processing](SOP-OPS-003-payroll-processing.md)
- [HRMS employees guide](../../hrms/user-guide/employees.md)
