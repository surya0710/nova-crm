# Error Codes

HTTP status codes and business rule errors extracted from the implementation.

---

## HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | Success | Normal response; includes soft empty states |
| 201 | Created | Resource created |
| 401 | Unauthenticated | Missing/invalid Bearer token |
| 403 | Forbidden | Missing permission, suspended/archived org, module not licensed |
| 404 | Not Found | Resource not in org; invalid route |
| 422 | Validation Error | Form validation or business rule violation |
| 429 | Too Many Requests | Rate limit exceeded (120/min API) |
| 500 | Server Error | Unhandled exception |
| 503 | Service Unavailable | Maintenance mode |

---

## Authentication Errors

| Status | Message | Cause |
|--------|---------|-------|
| 401 | Unauthenticated. | No token or expired/revoked token |

---

## Authorization Errors

| Status | Message | Cause |
|--------|---------|-------|
| 403 | This action is unauthorized. | Missing RBAC permission |
| 403 | API access is disabled for suspended organizations. | Org suspended |
| 403 | API access is disabled for archived organizations. | Org archived |
| 403 | Module not licensed. | Module not in org plan |

---

## Organization Context Errors

| Status | Message | Cause |
|--------|---------|-------|
| 422 | Organization context is required. | No tenant in `TenantContext` (lookups) |

---

## Attendance Business Errors `422`

From `AttendanceService` — field key `employee_id` unless noted:

| Message | Cause |
|---------|-------|
| Employee is not eligible for attendance recording. | Status not in `clockable_employee_statuses` |
| Cannot record attendance while on approved leave. | Approved leave on date |
| Cannot record attendance on a holiday. | Org/branch holiday |
| Cannot record attendance on a weekend. | Weekend day |
| Employee has already clocked in for this date. | Duplicate check-in |
| Employee must clock in before clocking out. | Check-out without check-in |
| Employee has already clocked out for this date. | Duplicate check-out |

Field `clock_out_at`:

| Message | Cause |
|---------|-------|
| Clock out must be after clock in. | Invalid time order |

---

## Attendance Correction Errors `422` (Web Only)

| Field | Message |
|-------|---------|
| `attendance_record_id` | A pending correction already exists for this attendance record. |
| `status` | Only pending corrections can be approved/rejected. |

---

## Identity Errors

| Status | Message | Cause |
|--------|---------|-------|
| 422 | This employee already has a login account. | Duplicate login account |
| 404 | — | User not in organization |
| 422 | — | User account disabled (password reset) |

Invitation activation `422`:

| Field | Cause |
|-------|-------|
| `token` | Invalid or expired invitation token |
| `password` | Password policy violation |

---

## Lookup Errors

| Status | Cause |
|--------|-------|
| 403 | Missing `hrms.view` or module not licensed |
| 404 | Unknown entity key |
| 422 | Organization context required |

---

## Soft Empty States `200`

Not errors — display appropriate UI:

| Message | audience | Cause |
|---------|----------|-------|
| No employee record is linked to your account. | `employee` | No `Employee` linked to user |
| No employees assigned. | `manager` | Manager has no direct reports |
| No employee records available. | `hr` | HR context empty |
| No team members found. | `supervisor` | Supervisor context empty |

Response includes `"empty_state": true`.

---

## Validation Error Format

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "The field name field is required."
    ]
  }
}
```

---

## Rate Limiting `429`

| Limiter | Limit | Scope |
|---------|-------|-------|
| `api` | 120/min | Per token ID or IP |
| `api-lead-intake` | 60/min | Lead creation |
| Identity activate | 10/min | Per IP |
| Web login | 5 attempts | Per email+IP |

Response:

```json
{
  "message": "Too Many Attempts."
}
```

---

## Client Error Handling Matrix

| HTTP | Action |
|------|--------|
| 401 | Clear token, show login/setup |
| 403 permission | Show "access denied" |
| 403 lifecycle | Show org suspended message |
| 403 module | Show "feature not available" |
| 404 | Show "not found" |
| 422 | Map `errors` to form fields |
| 200 + empty_state | Show empty state UI |
| 429 | Retry with exponential backoff |

---

## Record Not Found

Cross-org access returns 404 (not 403) to prevent information leakage.

Example: `GET /api/v1/tasks/999` where task belongs to different org.

---

## Dashboard Widget Errors

Widget not visible → may return empty data or 403 depending on `WidgetDataService` handling.

Missing `dashboard.view` → 403.

Missing widget-specific permission → widget excluded from list or returns `available: false`.
