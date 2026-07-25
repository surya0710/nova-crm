# Phase 10.1.5 — Leave Management Progress Report

## 1. Phase Summary

**Objective:** Build a production-grade Leave Management Platform as the single source of truth for employee leave within NovaCRM, integrated with Attendance (read-only), Workflow (events), Audit, and RBAC — without coupling to Payroll or ESS.

**Scope completed:** Leave types, holiday calendar, leave balances, balance ledger (`leave_balance_transactions`), leave applications (apply/edit/withdraw/cancel), half-day leave, multi-step approval (manager → optional HR), workflow events, audit logging, Blade UI, attendance read contract, and feature tests.

**Overall implementation status:** **Complete**

---

## 2. Features Delivered

| Feature | Status |
|---|---|
| Leave Type CRUD | ✅ |
| Holiday Calendar (org-wide + branch, recurring) | ✅ |
| Leave Balance Allocation | ✅ |
| Leave Balance Ledger | ✅ |
| Manual Balance Adjustment | ✅ |
| Leave Apply / Edit / Withdraw | ✅ |
| Half-Day Leave | ✅ |
| Overlap & Balance Validation | ✅ |
| Multi-Step Approval (Manager → HR) | ✅ |
| Leave Rejection (pending release) | ✅ |
| Leave Cancellation (balance restoration) | ✅ |
| Approval Queue UI | ✅ |
| Leave Dashboard | ✅ |
| Workflow Events (5 events) | ✅ |
| Audit Integration | ✅ |
| RBAC Integration | ✅ |
| Attendance Read Contract | ✅ |
| Payroll Read Contract (preserved) | ✅ |

---

## 3. Architecture

### Controller → FormRequest → Service → Model

All leave writes flow through `LeaveService` → models → `AuditLogger` → workflow events.

### Services

| Service | Path |
|---|---|
| `LeaveService` | `app/Services/Hrms/LeaveService.php` |

**Responsibilities:** leave type CRUD, holiday CRUD, balance allocation/adjustment, ledger recording, apply/update/withdraw/approve/reject/cancel leave, day calculation, daily leave lookup, dashboard stats.

### Controllers

| Controller | Path |
|---|---|
| `LeaveTypeController` | `app/Http/Controllers/Hrms/LeaveTypeController.php` |
| `HolidayController` | `app/Http/Controllers/Hrms/HolidayController.php` |
| `LeaveApplicationController` | `app/Http/Controllers/Hrms/LeaveApplicationController.php` |
| `LeaveBalanceController` | `app/Http/Controllers/Hrms/LeaveBalanceController.php` |
| `LeaveDashboardController` | `app/Http/Controllers/Hrms/LeaveDashboardController.php` |

### Form Requests

| Request | Path |
|---|---|
| `CreateLeaveTypeRequest` | `app/Http/Requests/Hrms/CreateLeaveTypeRequest.php` |
| `UpdateLeaveTypeRequest` | `app/Http/Requests/Hrms/UpdateLeaveTypeRequest.php` |
| `CreateHolidayRequest` | `app/Http/Requests/Hrms/CreateHolidayRequest.php` |
| `UpdateHolidayRequest` | `app/Http/Requests/Hrms/UpdateHolidayRequest.php` |
| `ApplyLeaveRequest` | `app/Http/Requests/Hrms/ApplyLeaveRequest.php` |
| `UpdateLeaveApplicationRequest` | `app/Http/Requests/Hrms/UpdateLeaveApplicationRequest.php` |
| `ApproveLeaveRequest` | `app/Http/Requests/Hrms/ApproveLeaveRequest.php` |
| `RejectLeaveRequest` | `app/Http/Requests/Hrms/RejectLeaveRequest.php` |
| `CancelLeaveRequest` | `app/Http/Requests/Hrms/CancelLeaveRequest.php` |
| `AdjustLeaveBalanceRequest` | `app/Http/Requests/Hrms/AdjustLeaveBalanceRequest.php` |

### Models

| Model | Path |
|---|---|
| `LeaveType` | `app/Models/LeaveType.php` |
| `Holiday` | `app/Models/Holiday.php` |
| `LeaveBalance` | `app/Models/LeaveBalance.php` |
| `LeaveBalanceTransaction` | `app/Models/LeaveBalanceTransaction.php` |
| `LeaveApplication` | `app/Models/LeaveApplication.php` |
| `LeaveApprovalStep` | `app/Models/LeaveApprovalStep.php` |

### Policies

| Policy | Path |
|---|---|
| `LeavePolicy` | `app/Policies/LeavePolicy.php` |
| `LeaveTypePolicy` | `app/Policies/LeaveTypePolicy.php` |
| `HolidayPolicy` | `app/Policies/HolidayPolicy.php` |

---

## 4. Database Changes

**Migration:** `database/migrations/2026_07_20_000006_extend_hrms_leave_management.php`

### Extended `leave_types`

- `allocation_days`
- `carry_forward_allowed`
- `negative_balance_allowed`
- `max_consecutive_days`

### Extended `holidays`

- `branch_id` (nullable — organization-wide when null)
- `is_recurring`
- Unique constraint updated to `(organization_id, holiday_date, branch_id)`

### New `leave_balance_transactions`

| Column | Purpose |
|---|---|
| `leave_balance_id` | FK to balance record |
| `transaction_type` | opening_balance, allocation, leave_submitted, leave_approved, etc. |
| `quantity` | Change amount |
| `balance_before` / `balance_after` | Snapshot |
| `remarks` | Human-readable note |
| `reference_type` / `reference_id` | Polymorphic link (e.g. leave application) |

Every balance mutation in `LeaveService` creates a ledger row — no exceptions.

---

## 5. Platform Integration

### Attendance (read-only)

`AttendanceService` delegates to `LeaveService`:

```php
AttendanceService::getApprovedLeaveForDate(Employee $employee, Carbon $date)
AttendanceService::isEmployeeOnLeave(Employee $employee, Carbon $date)
```

Attendance **never** approves leave or modifies balances.

### Workflow (react-only)

Business logic remains in `LeaveService`. Workflow listens via `RunTriggeredWorkflows`.

### Payroll (contract preserved)

Payroll may read via:

- `LeaveService::getApprovedLeaveForDate()`
- `LeaveService::getApprovedLeaveForDateRange()`
- `LeaveService::getBalancesForEmployee()`
- `leave_balance_transactions` ledger

No payroll logic implemented in this phase.

### Employee Platform

`LeaveService` validates employee status, joining date, and exit date on every application.

---

## 6. Workflow Events

| Event Class | Trigger Key |
|---|---|
| `LeaveSubmitted` | `leave.submitted` |
| `LeaveApproved` | `leave.approved` |
| `LeaveRejected` | `leave.rejected` |
| `LeaveCancelled` | `leave.cancelled` |
| `LeaveBalanceAdjusted` | `leave.balance_adjusted` |

All extend `WorkflowDomainEvent` and dispatch after commit.

---

## 7. Audit Integration

Explicit audit events via `AuditLogger`:

- `leave_type_created`, `leave_type_updated`, `leave_type_deleted`
- `holiday_created`, `holiday_updated`, `holiday_deleted`
- `leave_balance_allocated`, `leave_balance_adjusted`
- `leave_applied`, `leave_updated`, `leave_withdrawn`
- `leave_approval_step_approved`, `leave_approved`, `leave_rejected`, `leave_cancelled`

Models also use the `Auditable` trait for lifecycle events.

---

## 8. Testing Results

| Command | Result |
|---|---|
| `php artisan migrate` | ✅ Success |
| `php artisan test --filter=HrmsLeaveTest` | ✅ 14 passed (68 assertions) |
| `php artisan test` | ✅ 911 passed (3624 assertions) |
| `vendor/bin/pint --test` | ✅ Zero violations (after fix pass) |

---

## 9. Documentation Updated

| Document | Status |
|---|---|
| `docs/P10_PHASE_10_1_5_PROGRESS.md` | ✅ Created (this file) |
| `config/hrms.php` | ✅ Extended with transaction types, applicable statuses, cancellation cutoff |

---

## 10. Architectural Notes and Deferrals

**Deferred (explicitly out of scope):**

- ESS leave self-service pages
- Payroll integration implementation
- Automatic yearly allocation / scheduled accrual jobs
- Leave encashment runtime
- Mobile leave requests
- External calendar integrations

**Notes:**

- Approval steps are relational rows — complete history preserved, never overwritten.
- Balance formula: `balance = entitled - used - pending`.
- Manager approval requires matching pending step approver; HR steps require `leave.manage` or assigned approver.
- Default leave types seedable via `LeaveService::seedDefaultLeaveTypes()`.

---

## 11. Final Verification

- ✅ Production-ready
- ✅ Tenant isolation verified
- ✅ RBAC verified (`leave.view`, `leave.manage`, `leave.approve`)
- ✅ Audit verified
- ✅ Workflow verified (5 leave events registered)
- ✅ Attendance integration contract verified
- ✅ Payroll integration contract preserved
- ✅ Zero regression failures (911 tests)
- ✅ Phase ready to freeze
