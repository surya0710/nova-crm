# SOP-OPS-003 — Payroll Processing

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OPS-003 |
| **Title** | Payroll Processing |
| **Version** | 1.0 |
| **Effective Date** | 2026-08-06 |
| **Department** | Business Operations (Payroll) |
| **Owner** | Payroll Officer |
| **Reviewer** | Finance Partner / HR Admin |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Run a complete payroll cycle—from period readiness through calculation, approval, publication, payment marking, and payslip delivery—without manual SQL.

## Scope

- **In scope:** Periods, runs, adjustments, statutory checks, bank export, publication, payslip email queue, paid marking.
- **Out of scope:** Tax declaration verification details (SOP-OPS-004); attendance lock (SOP-OPS-002).

## Preconditions

- [ ] Payroll configuration and statutory rule sets active for the org
- [ ] Salary assignments current for included employees
- [ ] Attendance period locked for the payroll window (recommended)
- [ ] Income tax / TDS inputs current for the month (if India compliance enabled)
- [ ] Queue worker (or shared-host cron) running for payslip email

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| HR → Payroll | Period/run manage, approve, publish | Dynamic permissions |
| Finance exports | Bank export permissions | If used |
| Platform monitoring | Queue view | Ops only |

## Step-by-step Procedure

### 1. Period readiness

1. Create or open the payroll period.
2. Confirm attendance lock for matching dates (SOP-OPS-002).
3. Review pending payroll adjustments; approve or reject before calculate.

### 2. Calculate

1. Start payroll run / calculation for the period.
2. Monitor run status (`draft` → `running` → `calculated`).
3. Resolve calculation and statutory compliance errors before approval.
4. Recalculate after fixes; do not edit result rows in the database.

### 3. Approve

1. Review totals, exceptions, and sample employee results.
2. Approve the run (status → `approved`).
3. After approval, treat attendance reopen as blocked for this cycle.

### 4. Publish & payslips

1. Publish payroll (status → `published`).
2. Confirm ESS payslip visibility for employees.
3. Confirm `SendPayslipEmailJob` (or equivalent) drains on the mail queue; retry failed jobs via SOP-MNT-007 / SOP-MON-002.

### 5. Pay & export

1. Generate bank export if required; store artifact per finance policy.
2. Mark paid when remittance confirmed (status → `paid`).
3. For reversals, use the supported reversal flow only—never delete runs.

## Validation Checklist

- [ ] Run status progression recorded in UI and audit
- [ ] Tenant isolation: cannot open another org’s run by ID
- [ ] Payslips match published results
- [ ] Failed email jobs reviewed within 24h
- [ ] Bank export totals reconcile to net pay

## Failure Handling

| Symptom | Action |
|---------|--------|
| Run stuck in `running` | Check queue/worker; failed jobs; do not duplicate runs blindly |
| Statutory errors | Fix employee statutory profiles / rule set; recalculate |
| Payslip email failures | Fix mail config; `queue:retry`; see SOP-DEP-003 |

## Related SOPs / Docs

- [SOP-OPS-002 — Attendance Lock](SOP-OPS-002-attendance-lock-workflow.md)
- [SOP-OPS-004 — Tax Declaration](SOP-OPS-004-tax-declaration-workflow.md)
- [Payroll user guide](../../hrms/user-guide/payroll.md)
- [Queue monitoring](../monitoring/SOP-MON-002-queue-monitoring.md)
