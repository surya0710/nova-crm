# SOP-OPS-002 — Attendance Lock Workflow

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OPS-002 |
| **Title** | Attendance Lock Workflow |
| **Version** | 1.0 |
| **Effective Date** | 2026-08-06 |
| **Department** | Business Operations (HR) |
| **Owner** | HR / Time Administrator |
| **Reviewer** | Payroll Officer |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Freeze and lock an attendance period so payroll consumes a consistent snapshot, with controlled reopen rules when corrections are required.

## Scope

- **In scope:** Attendance periods, validation, freeze, lock, snapshot, reopen constraints tied to payroll status.
- **Out of scope:** Daily punch correction policy details; overtime rule configuration.

## Preconditions

- [ ] Attendance period created (or auto-created for a payroll period)
- [ ] Corrections and overtime approvals completed for the window
- [ ] Actor has attendance period manage / lock permissions

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| HR → Attendance periods | Attendance period manage | Policy-backed |
| Payroll (read) | Payroll period/run view | To confirm blocking statuses |

## Step-by-step Procedure

### 1. Open period hygiene

1. Resolve open corrections and missing attendance for the period dates.
2. Run period validation; fix blocking errors before freeze/lock.
3. Review warnings (document acceptance if proceeding).

### 2. Freeze (optional staging)

1. Freeze the period (`open` → `frozen`) when edits should pause but lock is not final.
2. Confirm audit event `attendance_frozen`.
3. Only reopen/edit per policy if freeze was premature.

### 3. Lock + snapshot

1. Lock the period (`open` or `frozen` → `locked`).
2. Confirm an attendance snapshot version is generated.
3. Confirm attendance records in range receive `locked_at` / `locked_by`.
4. Confirm audit event `attendance_locked` with snapshot id/version.

### 4. Hand off to payroll

1. Proceed to payroll calculation only after lock succeeds (SOP-OPS-003).
2. Do not reopen if payroll run is in a **blocking** status: `approved`, `published`, `reversed`, `paid`, `locked`.

### 5. Reopen (exception only)

1. If period must reopen and payroll is still draft-like (`draft`, `running`, `calculated`), expect draft payroll invalidation per service rules.
2. Re-validate and re-lock before any new payroll calculation.
3. Record business reason in the ticket; audit will capture actor.

## Validation Checklist

- [ ] Status transitions follow open → (frozen) → locked
- [ ] Snapshot exists for locked period
- [ ] Cross-tenant: another org’s period IDs return 404/403
- [ ] Blocking payroll statuses prevent unsafe reopen
- [ ] Queue/notifications (if any) do not expose other tenants

## Failure Handling

| Symptom | Action |
|---------|--------|
| Validation fails on freeze/lock | Fix listed error codes; do not bypass via DB |
| Lock blocked | Confirm period status; refresh page; check concurrent actor |
| Payroll already approved | Do not reopen; use payroll correction / next-period adjustment process |

## Related SOPs / Docs

- [SOP-OPS-003 — Payroll Processing](SOP-OPS-003-payroll-processing.md)
- Service: `App\Services\Hrms\AttendanceLockService`
- [Attendance user guide](../../hrms/user-guide/attendance.md)
