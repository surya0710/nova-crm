# Phase 10.3.2 — Payroll Calculation Engine Progress Report

## 1. Phase Summary

**Objective:** Build a deterministic, repeatable, and auditable Payroll Calculation Engine on top of Phase 10.3.1 Payroll Foundation.

**Scope completed:** Payroll runs, payroll results, salary calculation (fixed/percentage earnings, non-statutory deductions), preview, immutable snapshots, validation, recalculation, workflow events, audit, RBAC, Blade UI, and feature tests.

**Overall implementation status:** **Complete — ready to freeze**

---

## 2. Features Delivered

| Feature | Status |
|---|---|
| Payroll Runs | ✅ |
| Payroll Results (per employee per run) | ✅ |
| Salary Calculation Engine | ✅ |
| Payroll Preview (non-persisting) | ✅ |
| Immutable Payroll Snapshots + hash | ✅ |
| Payroll Validation + error records | ✅ |
| Recalculation (draft/running only) | ✅ |
| Workflow events | ✅ |
| Audit integration | ✅ |
| RBAC (`payroll.calculate`) | ✅ |
| Tenant isolation | ✅ |
| Blade UI (dashboard, runs, preview, results) | ✅ |
| Feature tests | ✅ |

### Explicitly deferred

- PF / ESI / PT / TDS / income tax engines
- Payslips, approval workflow, bank transfers, accounting journals, final settlement

---

## 3. Architecture

```
Controller → FormRequest → PayrollCalculationService → PayrollService → Models
```

| Layer | Files |
|---|---|
| Service | `App\Services\Hrms\PayrollCalculationService` |
| Foundation | `App\Services\Hrms\PayrollService` (context + CRUD) |
| Models | `PayrollRun`, `PayrollResult`, `PayrollValidationError` |
| Controllers | `PayrollRunController`, `PayrollResultController` (+ dashboard updates) |
| Policies | `PayrollRunPolicy`, `PayrollResultPolicy` |
| Events | `PayrollRunStarted`, `PayrollRunCompleted`, `PayrollEmployeeCalculated`, `PayrollValidationFailed` |

Business logic for calculation lives only in `PayrollCalculationService`. `PayrollService` continues to own configuration and foundation CRUD/context resolution.

---

## 4. Database Changes

**Migrations:**

- `2026_07_20_000014_create_payroll_calculation_tables.php`
- `2026_07_20_000015_sync_payroll_calculation_permissions.php`

**Tables:**

| Table | Purpose |
|---|---|
| `payroll_runs` | Period-linked run with status lifecycle |
| `payroll_results` | Immutable per-employee result + snapshot JSON + hash |
| `payroll_validation_errors` | Validation failures for a run |

**Run statuses:** `draft`, `running`, `calculated`, `approved` (future), `published` (future)

---

## 5. Payroll Calculation Flow

1. Create draft `PayrollRun` for an open/draft period
2. Start calculation → status `running` → emit `payroll.run.started`
3. For each eligible employee:
   - Validate (active status, salary assignment, period open, config present, no duplicate)
   - On failure → persist `PayrollValidationError` → emit `payroll.validation.failed`
   - On success → calculate via shared engine → persist `PayrollResult` → emit `payroll.employee.calculated`
4. Complete → status `calculated` → emit `payroll.run.completed`

**Engine rules (10.3.2):**

- Resolve context via `PayrollService::resolveCalculationContext`
- Prorate recurring earnings by `payable_days / working_days_per_month`
- Fixed then percentage components (percentage based on `based_on_component_id` or sum of fixed)
- Skip statutory codes (`PF`, `ESI`, `PT`, `IT`, `TDS`) and formula placeholders
- Optional overtime pay from attendance overtime minutes when config is `pay`
- Round per organization rounding policy
- Snapshot + SHA-256 calculation hash for reproducibility

**Preview:** same `calculateEmployeePayroll` / validation path; no persistence.

**Recalculation:** allowed only when run status is `draft` or `running`. Calculated runs are immutable (create a new run instead).

---

## 6. Platform Integration

| Platform | Consumption |
|---|---|
| Payroll Foundation | Salary assignment, structure components, configuration, periods |
| Employee | Status, joining/exit dates (read-only) |
| Attendance | Working days + overtime minutes from records (no recalculation) |
| Leave | Approved leave / unpaid days via `LeaveService` |

---

## 7. Workflow Events

| Trigger | Event |
|---|---|
| `payroll.run.started` | `PayrollRunStarted` |
| `payroll.run.completed` | `PayrollRunCompleted` |
| `payroll.employee.calculated` | `PayrollEmployeeCalculated` |
| `payroll.validation.failed` | `PayrollValidationFailed` |

Registered with `RunTriggeredWorkflows` in `AppServiceProvider`. Documented in `config/hrms.php` → `workflow_triggers`.

---

## 8. Audit Integration

| Event | When |
|---|---|
| `payroll_run_created` | Run created |
| `payroll_calculation_started` | Calculation begins |
| `payroll_calculation_completed` | Calculation finishes |
| `payroll_recalculated` | Recalculation invoked |

---

## 9. Testing Results

**Command:** `php artisan test --filter=HrmsPayrollCalculationTest`

```
Tests:    11 passed (58 assertions)
```

Coverage includes: run creation, employee calculation, preview, snapshots/hash, duplicate prevention, recalculation rules, validation errors, locked period rejection, tenant isolation, RBAC, workflow, audit, deterministic fixed/percentage math.

**Migrations:** `php artisan migrate` — applied  
**Formatting:** `php vendor/bin/pint --dirty`

**Full regression:** `php artisan test`

```
Tests:    956 passed (3854 assertions)
Duration: 433.50s
```

**Formatting:** `php vendor/bin/pint --dirty` — clean

**Migrations:** `php artisan migrate` — applied

---

## 10. Documentation Updated

| Document | Change |
|---|---|
| `docs/P10_PHASE_10_3_2_PROGRESS.md` | This verification report |
| `docs/P10_HRMS_PHASE_DEVELOPMENT.md` | Phase 10.3 calculation status |
| `config/hrms.php` | Run statuses, statutory codes, workflow triggers |
| `config/rbac.php` | `payroll.calculate` + HR grant |

---

## 11. Architectural Notes

- Single calculation path for preview and persisted runs guarantees identical hashes.
- Results are append-only for a completed run; uniqueness enforced on `(org, run, employee)`.
- Statutory component codes are skipped (amount 0, status `skipped_statutory`) — engines arrive in later phases.
- Formula calculation type remains a placeholder (`skipped_formula`).

---

## 12. Final Verification

| Criterion | Status |
|---|---|
| Production-ready calculation engine | ✅ |
| Tenant isolation verified | ✅ |
| RBAC verified | ✅ |
| Audit verified | ✅ |
| Workflow verified | ✅ |
| Deterministic calculations | ✅ |
| Immutable payroll snapshots | ✅ |
| Zero regression failures | ✅ |
| Phase ready to freeze | ✅ |

**Phase 10.3.2 is complete and ready to freeze.**
