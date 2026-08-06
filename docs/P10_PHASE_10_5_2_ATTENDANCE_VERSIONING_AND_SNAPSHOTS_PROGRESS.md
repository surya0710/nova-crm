# Phase 10.5.2 — Attendance Versioning & Snapshot Foundation Progress Report

## 1. Phase Summary

**Objective:** Establish an immutable attendance foundation (versioning, periods, freeze/lock, snapshots) so Payroll and future modules consume historical attendance safely without live-record drift.

**Scope completed:** Attendance versioning, calculation extraction, working-time calculator, attendance periods (Open → Frozen → Locked), validation, lock lifecycle, immutable snapshots + re-lock supersession, payroll snapshot-only consumption, reopen protection against approved/published payroll, RBAC, audit events, Blade period UI, and feature tests.

**Overall implementation status:** **Complete — ready for verification**

---

## 2. Features Delivered

| Feature | Status |
|---|---|
| `WorkingTimeCalculator` (pure, no Eloquent) | ✅ |
| `AttendanceCalculationService` | ✅ |
| `AttendanceVersionService` | ✅ |
| `AttendanceValidationService` (structured codes) | ✅ |
| `AttendanceLockService` (freeze / lock / reopen) | ✅ |
| `AttendanceSnapshotService` | ✅ |
| Attendance periods (`open` / `frozen` / `locked`) | ✅ |
| Snapshot versioning + supersession | ✅ |
| Payroll consumes snapshots only | ✅ |
| Historical payroll stability after live edits | ✅ |
| Reopen blocked for approved/published/paid payroll | ✅ |
| Draft payroll invalidated on reopen | ✅ |
| RBAC `attendance.lock`, `attendance.export` | ✅ |
| Audit events | ✅ |
| Feature tests | ✅ |

### Explicitly deferred

- Device / biometric / QR / geo-fencing integrations
- Attendance dashboards & workforce analytics
- Advanced attendance reports beyond period validation UI

---

## 3. Architecture

```
Controller → FormRequest → AttendanceService (orchestration)
                ↓
   Focused services (Version / Calculation / Lock / Validation / Snapshot)
                ↓
              Models
```

`AttendanceService` remains the single orchestration layer for clocking and corrections. Period lock lifecycle is exposed via `AttendancePeriodController` → `AttendanceLockService`.

Payroll path:

```
AttendanceRecord (live)
        ↓ (on period lock)
AttendanceSnapshot (+ rows)
        ↓
PayrollService::resolveCalculationContext
        ↓
PayrollCalculationService
```

No live attendance fallback for payroll.

---

## 4. Database Changes

**Migrations:**

- `2026_08_01_000020_create_attendance_versioning_and_snapshot_tables.php`
- `2026_08_01_000021_sync_attendance_versioning_permissions.php`

**Extended tables:**

| Table | Columns added |
|---|---|
| `attendance_records` | `version`, `approval_status`, `break_minutes`, `locked_at`, `locked_by` |
| `attendance_corrections` | `target_version`, `resulting_version`, `current_step`, `requires_hr_approval` |
| `hrms_shifts` | `late_threshold_minutes`, `early_exit_threshold_minutes`, `maximum_working_minutes`, `overtime_allowed` |

**New tables:**

| Table | Purpose |
|---|---|
| `attendance_record_versions` | Immutable history of prior live states |
| `attendance_periods` | Open / Frozen / Locked lifecycle aligned with payroll periods |
| `attendance_snapshots` | Immutable period snapshots with hash + version |
| `attendance_snapshot_rows` | Per-employee/day frozen attendance payload |

---

## 5. Services

| Service | Responsibility |
|---|---|
| `WorkingTimeCalculator` | Working/break/late/early/OT minutes; overnight shifts |
| `AttendanceCalculationService` | Metrics + leave/holiday/weekend day context |
| `AttendanceVersionService` | Archive prior state, increment live `version` |
| `AttendanceValidationService` | Pre-freeze/lock checks with error codes |
| `AttendanceSnapshotService` | Generate / supersede snapshots; payroll summarize |
| `AttendanceLockService` | Period CRUD lifecycle + payroll reopen guards |

### Validation error codes

- `missing_checkout`
- `pending_correction`
- `pending_approval`
- `missing_shift`
- `invalid_hours`
- `duplicate_attendance`
- `unapproved_overtime`

Warnings: `long_working_hours`, `missing_break`

---

## 6. Payroll Integration

`PayrollService::resolveCalculationContext()` now:

1. Requires a **locked** attendance period for the payroll period (by `payroll_period_id` or matching dates)
2. Requires an **active** attendance snapshot
3. Aggregates attendance exclusively from `attendance_snapshot_rows`

Failure modes (no live fallback):

- Attendance not locked
- Snapshot missing

### Reopen protection

| Payroll run status | Reopen behavior |
|---|---|
| `draft` / `running` / `calculated` | Allowed; draft results cleared / run reset to draft |
| `approved` / `published` / `reversed` / `paid` / `locked` | Blocked — requires explicit rollback workflow |

---

## 7. RBAC

New permissions:

- `attendance.lock` — freeze / lock / reopen periods
- `attendance.export` — export attendance (reserved for reports)

Granted to HR (and `*` roles) via config + sync migration. No role-name checks in services.

---

## 8. Audit Events

- `attendance_version_created`
- `attendance_period_created`
- `attendance_frozen`
- `attendance_locked`
- `attendance_reopened`
- `attendance_snapshot_generated`
- `attendance_snapshot_superseded`
- `payroll_draft_invalidated_by_attendance_reopen`

---

## 9. Tests

**Primary:** `tests/Feature/AttendanceVersioningAndSnapshotTest.php`

Covers version history, freeze/lock/reopen, snapshot immutability/supersession, validation failures, payroll snapshot consumption, historical stability, reopen blocked when payroll approved, tenant isolation, HTTP permission checks.

**Payroll suites updated** to lock attendance before calculation via `Tests\Support\LocksAttendanceForPayroll`.

---

## 10. Known Limitations

- Period UI is operational (list/create/show/validate) — not a full attendance analytics suite
- `attendance.export` is permission-ready; dedicated snapshot export endpoints are not yet built
- Individual record `locked_at` is set on period lock; unlock clears it on reopen
- “Paid” payroll status is reserved for future bank-payment completion; approved/published/reversed already block reopen
- Existing attendance workflows (clock, corrections, overtime) are preserved; versioning is additive

---

## 11. Verification Checklist

- [ ] Run migrations `2026_08_01_000020` and `2026_08_01_000021`
- [ ] `php artisan test --filter=AttendanceVersioningAndSnapshotTest`
- [ ] `php artisan test --filter=HrmsPayroll`
- [ ] `php artisan test --filter=HrmsAttendanceTest`
- [ ] Freeze a period → employee clock blocked; HR correction still allowed
- [ ] Lock → snapshot generated → payroll calculates
- [ ] Edit live attendance after reopen → re-lock → payroll uses snapshot V2
- [ ] Approve payroll → reopen attendance blocked
