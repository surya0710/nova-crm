# SOP-OPS-004 — Tax Declaration Workflow

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OPS-004 |
| **Title** | Tax Declaration Workflow |
| **Version** | 1.0 |
| **Effective Date** | 2026-08-06 |
| **Department** | Business Operations (Payroll / Tax) |
| **Owner** | Payroll / Tax Administrator |
| **Reviewer** | Finance Partner |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Operate the Indian income-tax declaration cycle: financial year readiness, regime selection, employee declarations, proof verification, TDS projection/calculation, and Form 16 generation hooks.

## Scope

- **In scope:** Financial year/slabs, regime, declarations, proofs, verification/rejection, TDS monthly calc inputs, projections.
- **Out of scope:** Full CA advisory; statutory PF/ESI setup outside tax module.

## Preconditions

- [ ] HRMS Income Tax features enabled for the organization
- [ ] Active tax financial year with slabs (auto-seed via service if missing)
- [ ] Employees have PAN / statutory profile where required
- [ ] Actor permissions for tax FY, declarations, proofs (policies)

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| HR → Income Tax | FY / declaration / proof manage | Policy-backed |
| ESS / Mobile tax APIs | Employee self declaration | Sanctum for mobile |
| Payroll | Run view | For TDS impact |

## Step-by-step Procedure

### 1. Open financial year

1. Confirm active FY and regime slabs (old/new as configured).
2. Publish declaration window dates to employees (comms outside or via announcements).

### 2. Employee regime + declaration

1. Employee selects tax regime for the FY (ESS / mobile).
2. Employee creates declaration (`draft`), adds items, submits (`submitted`).
3. HR monitors dashboard counts (draft / submitted / verified / rejected).

### 3. Proof upload & verify

1. Employee uploads proofs (status `uploaded` / `partial`).
2. Tax admin verifies or rejects with reason (`verified` / `rejected`).
3. Verified declarations (`STATUS_VERIFIED`) feed projection/deduction totals (regime-aware).

### 4. TDS & payroll alignment

1. Run/refresh tax projections for sample employees.
2. Ensure monthly TDS calculation jobs/screens completed before payroll approve.
3. After payroll publication, avoid changing verified declarations without a controlled correction process.

### 5. Form 16 / year end

1. Generate Form 16 records for eligible employees when year-end package is ready.
2. Confirm download permissions are employee-scoped and tenant-scoped.
3. Retain artifacts per finance retention policy (SOP-OFF-005).

## Validation Checklist

- [ ] No cross-tenant declaration/proof access by ID
- [ ] Rejected declarations cannot silently count as approved deductions
- [ ] New-regime deduction rules respected in projections
- [ ] Audit events for submit / approve / reject / proof verify
- [ ] Mobile API responses use standard `ApiResponse` errors

## Failure Handling

| Symptom | Action |
|---------|--------|
| No active FY | Use income tax admin to activate/seed FY; do not insert slabs manually unless directed |
| Proof virus/upload failure | Confirm upload validator + storage disk; retry |
| TDS mismatch vs payslip | Recalculate projection after verified totals; then payroll recalculation if still draft |

## Related SOPs / Docs

- [SOP-OPS-003 — Payroll Processing](SOP-OPS-003-payroll-processing.md)
- Phase notes: `docs/P10_PHASE_10_3_7_INDIAN_INCOME_TAX_PROGRESS.md`
- Services: `IncomeTaxService`, `InvestmentDeclarationService`, `TaxProofService`, `TdsCalculationService`
