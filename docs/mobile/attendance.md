# Attendance APIs

Extracted from `routes/api_attendance.php`, `AttendanceDashboardApiController`, `AttendanceService`, `AttendanceDashboardService`, and `EssClockInRequest` / `EssClockOutRequest`.

**Base path:** `/api/v1/attendance`

---

## Global Requirements

| Requirement | Value |
|-------------|-------|
| Authentication | Sanctum Bearer token |
| Headers | `Authorization`, `X-Organization-Id`, `Accept: application/json` |
| Middleware | `auth:sanctum`, `throttle:api` (120/min), `set.organization`, `ensure.organization`, `organization.api` |
| Employee link | User must have `Employee` record in current org (`EssContext::requireEmployee`) |
| Module license | `hrms` |

---

## Endpoints

### GET `/api/v1/attendance/dashboard`

Employee attendance dashboard for today.

| Property | Value |
|----------|-------|
| **Permission** | `ess.access` |
| **Route name** | `api.attendance.dashboard` |

#### Request

No body or query parameters.

#### Success Response `200`

```json
{
  "data": {
    "date": "2026-07-21",
    "state": "checked_in",
    "state_label": "Checked In",
    "working_hours": {
      "worked_minutes": 222,
      "worked_label": "3h 42m",
      "expected_minutes": 540,
      "expected_label": "9h 00m",
      "remaining_minutes": 318,
      "remaining_label": "5h 18m",
      "overtime_minutes": 0,
      "is_live": true,
      "clock_in_at": "2026-07-21T09:04:00+00:00",
      "clock_out_at": null
    },
    "shift_info": {
      "available": true,
      "name": "General Shift",
      "code": "GEN",
      "start_time": "09:00",
      "end_time": "18:00",
      "break_minutes": 60,
      "branch": "Head Office",
      "phase": "current",
      "phase_label": "Current shift"
    },
    "indicator": {
      "key": "on_time",
      "label": "On Time",
      "color": "green"
    },
    "actions": {
      "can_check_in": false,
      "can_check_out": true,
      "check_in_url": "https://host/hrms/ess/attendance/clock-in",
      "check_out_url": "https://host/hrms/ess/attendance/clock-out",
      "blocked_reason": null
    },
    "on_leave_today": false
  }
}
```

> **Mobile note:** Use `POST /attendance/check-in` and `POST /attendance/check-out` — ignore `check_in_url` / `check_out_url` (web routes).

#### State Values

| State | Description |
|-------|-------------|
| `not_checked_in` | No clock-in today |
| `checked_in` | Clocked in, not out |
| `checked_out` | Completed for today |
| `on_leave` | Approved leave covers today |
| `holiday` | Organization/branch holiday |
| `weekend` | Configured weekend day |

#### Indicator Values

| key | color | When |
|-----|-------|------|
| `missing_checkout` | red | Past shift end, still checked in |
| `late` | orange | `late_minutes > 0` |
| `early` | yellow | Early arrival or early departure |
| `on_time` | green | Within grace period |
| `null` | — | on_leave, holiday, weekend, not_checked_in |

#### Shift Phase Values

`upcoming` | `current` | `completed`

#### Error Responses

| Status | Condition |
|--------|-----------|
| 403 | Missing `ess.access` |
| 200 + empty_state | No linked employee record |

Empty state response (`MissingEmployeeRecordException`):

```json
{
  "message": "No employee record is linked to your account.",
  "empty_state": true,
  "audience": "employee"
}
```

---

### POST `/api/v1/attendance/check-in`

| Property | Value |
|----------|-------|
| **Permission** | `ess.access` + `EmployeePolicy::clock` |
| **Form request** | `EssClockInRequest` |
| **Source recorded** | `api` |

#### Request Body

| Field | Rules |
|-------|-------|
| `clock_in_at` | nullable, date — defaults to `now()` if omitted |

#### Success Response `200`

```json
{
  "message": "Checked in successfully.",
  "record": {
    "id": 1,
    "organization_id": 42,
    "employee_id": 10,
    "shift_id": 3,
    "attendance_date": "2026-07-21",
    "clock_in_at": "2026-07-21T09:00:00+00:00",
    "clock_out_at": null,
    "status": "pending",
    "source": "api",
    "working_minutes": null,
    "late_minutes": 0,
    "early_departure_minutes": 0,
    "overtime_minutes": 0,
    "employee": { },
    "shift": { }
  },
  "dashboard": { }
}
```

`dashboard` object has same shape as `GET /dashboard` → `data`.

#### Error Responses `422`

| Field | Message |
|-------|---------|
| `employee_id` | Employee is not eligible for attendance recording |
| `employee_id` | Cannot record attendance while on approved leave |
| `employee_id` | Cannot record attendance on a holiday |
| `employee_id` | Cannot record attendance on a weekend |
| `employee_id` | Employee has already clocked in for this date |
| `clock_in_at` | Validation failed (invalid date) |

#### Example

```http
POST /api/v1/attendance/check-in HTTP/1.1
Authorization: Bearer {token}
X-Organization-Id: 42
Content-Type: application/json

{
  "clock_in_at": "2026-07-21T09:00:00"
}
```

---

### POST `/api/v1/attendance/check-out`

| Property | Value |
|----------|-------|
| **Permission** | `ess.access` + `EmployeePolicy::clock` |
| **Form request** | `EssClockOutRequest` |

#### Request Body

| Field | Rules |
|-------|-------|
| `clock_out_at` | nullable, date — defaults to `now()` if omitted |

#### Success Response `200`

```json
{
  "message": "Checked out successfully.",
  "record": {
    "clock_out_at": "2026-07-21T18:00:00+00:00",
    "status": "present",
    "working_minutes": 480,
    "late_minutes": 0,
    "early_departure_minutes": 0,
    "overtime_minutes": 0
  },
  "dashboard": { }
}
```

#### Error Responses `422`

| Field | Message |
|-------|---------|
| `employee_id` | Employee must clock in before clocking out |
| `employee_id` | Employee has already clocked out for this date |
| `clock_out_at` | Clock out must be after clock in |
| (same as check-in) | leave, holiday, weekend, eligibility |

---

### GET `/api/v1/attendance/team-summary`

Manager team attendance KPIs for today.

| Property | Value |
|----------|-------|
| **Permission** | `manager.dashboard` |
| **Cache** | 60 seconds per org+date |
| **Scope** | Direct reports only (`reporting_manager_id`) |

#### Success Response `200`

```json
{
  "data": {
    "date": "2026-07-21",
    "team_count": 10,
    "present": 8,
    "absent": 1,
    "leave": 1,
    "late": 2,
    "working": 5,
    "checked_out": 3,
    "not_checked_in_count": 1,
    "late_employees": [
      {
        "employee_id": 42,
        "name": "Jane Doe",
        "late_minutes": 15,
        "clock_in_at": "2026-07-21T09:15:00+00:00"
      }
    ],
    "attendance_url": "https://host/hrms/attendance"
  }
}
```

Empty team: all counts `0`, `late_employees: []`.

---

## Business Rules

From `AttendanceService` and `config/hrms.php`:

### Clockable Employee Statuses

Only employees with status in `clockable_employee_statuses`:

- `active`
- `probation`
- `notice_period`

### Blocked Dates

Attendance recording blocked when:

1. **Approved leave** covers the date
2. **Holiday** — org-wide (`branch_id` null) or employee's branch
3. **Weekend** — `config('hrms.weekend_days')` default `saturday`, `sunday` (overridable per org in `organizations.settings.weekend_days`)

### Duplicate Prevention

- One check-in per employee per date
- One check-out per employee per date
- `clock_out_at` must be after `clock_in_at`

### Shift Resolution

Latest `EmployeeShiftAssignment` where:

- `effective_from <= date`
- `effective_to` is null OR `>= date`

### Metrics on Checkout

With assigned shift:

- **Late minutes:** clock-in after `shift_start + grace_period_minutes`
- **Early departure:** clock-out before shift end
- **Working minutes:** gross duration minus `break_minutes`
- **Overtime:** `max(0, working_minutes - overtime_threshold)`
- **Status:** `late` > `half_day` > `present` (late takes precedence)

Without shift: gross working minutes, status `present`.

### Attendance Statuses

From `config/hrms.php` → `attendance_statuses`:

`present`, `absent`, `late`, `half_day`, `on_leave`, `holiday`, `weekend`, `pending`

### Attendance Sources

`manual`, `biometric`, `mobile`, `api`, `import`

API check-in records `source: api`.

---

## Missing Endpoints (Web Only)

| Feature | Web route | Controller |
|---------|-----------|------------|
| Attendance history | `GET /hrms/ess/attendance` | `EssAttendanceController@index` |
| Submit correction | `POST /hrms/ess/attendance/corrections` | `EssAttendanceController@storeCorrection` |
| Monthly summary | `GET /hrms/attendance` | `HrmsAttendanceController` (HR admin) |

Correction validation (`EssAttendanceCorrectionRequest`):

| Field | Rules |
|-------|-------|
| `attendance_record_id` | required, integer, exists |
| `requested_clock_in_at` | nullable, date |
| `requested_clock_out_at` | nullable, date |
| `reason` | required, string, max:2000 |

---

## Data Not Exposed via API

`AttendanceDashboardService::employeeSummary()` computes but API strips:

- `recent_attendance` (last 5 records)
- `upcoming_holidays` (next 5 holidays)
- `record` (raw attendance record)
- `shift` (raw shift model)

Consider requesting backend extension for mobile history/holiday widgets.
