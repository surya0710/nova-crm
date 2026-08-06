# Leave Management APIs

**Status: No dedicated REST API endpoints exist for leave management.**

Leave operations are implemented as **web ESS routes** only. This document records the web implementation for future API parity and documents the partial workaround via dashboard widgets.

---

## API Availability

| Endpoint | Status |
|----------|--------|
| `GET /api/v1/leave/balance` | **Not implemented** |
| `GET /api/v1/leave/types` | **Not implemented** |
| `POST /api/v1/leave` | **Not implemented** |
| `PUT /api/v1/leave/{id}` | **Not implemented** |
| `DELETE /api/v1/leave/{id}` | **Not implemented** |
| `GET /api/v1/leave/history` | **Not implemented** |
| `GET /api/v1/leave/pending` | **Not implemented** |
| `POST /api/v1/leave/{id}/approve` | **Not implemented** |
| `POST /api/v1/leave/{id}/reject` | **Not implemented** |

### Partial Workaround

`GET /api/v1/dashboard/widgets/leave_balance/data`

Requires `leave.view` permission and `hrms` module license.

```json
{
  "balances": [
    {
      "leave_type": "Annual Leave",
      "available": 12.5
    }
  ],
  "available": true
}
```

Does **not** include: leave types list, applications, transactions, or apply/withdraw.

---

## Web ESS Implementation (Reference)

**Prefix:** `/hrms/ess/leave`  
**Middleware:** `permission:ess.access`  
**Controller:** `EssLeaveController`

| Method | Web route | Action |
|--------|-----------|--------|
| GET | `/hrms/ess/leave` | List balances, applications, transactions |
| POST | `/hrms/ess/leave` | Apply for leave |
| DELETE | `/hrms/ess/leave/{application}` | Withdraw own application |

---

## Apply Leave — Web Validation

**FormRequest:** `EssApplyLeaveRequest`  
**Authorization:** `EmployeePolicy::applyLeave` (requires `ess.access`)

| Field | Rules |
|-------|-------|
| `leave_type_id` | required, integer, exists in `leave_types` for org |
| `start_date` | required, date |
| `end_date` | required, date, after_or_equal:start_date |
| `is_half_day` | sometimes, boolean |
| `half_day_period` | nullable, string, in `config('hrms.half_day_periods')` keys |
| `reason` | nullable, string, max:2000 |

### Half Day Periods

From `config/hrms.php`:

| Key | Label |
|-----|-------|
| `first_half` | First half |
| `second_half` | Second half |

---

## Leave Statuses

From `config/hrms.php` → `leave_statuses`:

| Status | Label |
|--------|-------|
| `draft` | Draft |
| `pending` | Pending |
| `approved` | Approved |
| `rejected` | Rejected |
| `cancelled` | Cancelled |

---

## Leave Applicable Employee Statuses

`active`, `probation`, `notice_period`

---

## HR Admin Leave (Web Only)

| Feature | Permission | Web prefix |
|---------|------------|------------|
| View leave | `leave.view` | `/hrms/leave-*` |
| Manage leave | `leave.manage` | `/hrms/leave-*` |
| Approve/reject | `leave.approve` | `/hrms/leave-applications` |

Manager approval flows exist in web UI only.

---

## Permissions

| Permission | Slug | Mobile relevance |
|------------|------|------------------|
| ESS access | `ess.access` | Required for apply (web) |
| View leave | `leave.view` | Leave balance widget |
| Manage leave | `leave.manage` | HR admin |
| Approve leave | `leave.approve` | Manager approvals |

Default `employee` role: `leave.view`  
Default `manager` role: `leave.view`, `leave.approve`

---

## Business Rules (LeaveService)

Implemented in `App\Services\Hrms\LeaveService`:

- Balance validation before application
- Overlap detection with existing applications
- Half-day support per leave type `allow_half_day`
- Approval workflow per leave type (`requires_approval`, `requires_hr_approval`)
- Withdrawal via `withdrawLeave()` for own pending applications
- Cancellation cutoff: `config('hrms.leave_cancellation_cutoff_days')` default 0

---

## Recommended Future API

When implemented, mirror `EssLeaveController`:

```
GET    /api/v1/ess/leave/balances
GET    /api/v1/ess/leave/types
GET    /api/v1/ess/leave/applications
POST   /api/v1/ess/leave/applications
DELETE /api/v1/ess/leave/applications/{id}
GET    /api/v1/ess/leave/transactions

# Manager
GET    /api/v1/leave/pending-approvals
POST   /api/v1/leave/applications/{id}/approve
POST   /api/v1/leave/applications/{id}/reject
```

Use same validation rules from `EssApplyLeaveRequest`.

---

## Mobile Screen Mapping (Current)

```
Leave Screen
  ↓
GET /dashboard/widgets/leave_balance/data  (summary only)
  ↓
[No API for apply/history — web fallback or blocked]
```
