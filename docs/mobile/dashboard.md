# Employee Dashboard APIs

Dashboard and widget endpoints for the ESS (Employee Self-Service) home screen.

**Base path:** `/api/v1/dashboard`

---

## Global Requirements

| Requirement | Value |
|-------------|-------|
| Permission (base) | `dashboard.view` |
| Middleware | `auth:sanctum`, `throttle:api`, `set.organization`, `ensure.organization`, `organization.api` |
| Module license | Per widget (`hrms`, `notifications`, etc.) |

---

## Dashboard Endpoints

### GET `/api/v1/dashboard`

Full dashboard payload with sections and widgets.

**Controller:** `DashboardApiController@index`  
**Service:** `DashboardService::build()`

#### Response

```json
{
  "organization_id": 42,
  "sections": [
    {
      "slug": "overview",
      "name": "Overview",
      "widgets": []
    },
    {
      "slug": "hrms",
      "name": "HRMS",
      "widgets": []
    }
  ],
  "widgets": []
}
```

Widget visibility filtered by user permissions and module licenses.

---

### GET `/api/v1/dashboard/workspace`

Workspace context for current user.

**Controller:** `WorkspaceController@show`

---

### GET `/api/v1/dashboard/widgets`

List available widgets for current user/org.

#### Response

```json
{
  "widgets": [
    {
      "id": 1,
      "widget_key": "employee_attendance",
      "name": "Attendance Status",
      "module": "hrms",
      "is_visible": true
    }
  ]
}
```

---

### GET `/api/v1/dashboard/widgets/{widgetKey}/data`

Lazy-load widget data by key.

**Controller:** `DashboardWidgetController@data`  
**Service:** `WidgetDataService::lazyLoad()`

#### HRMS Widget Keys

| widgetKey | Permission | Module | Data |
|-----------|------------|--------|------|
| `mark_attendance` | `ess.access` | `hrms` | Quick clock status |
| `employee_attendance` | `ess.access` | `hrms` | Full attendance summary |
| `working_hours` | `ess.access` | `hrms` | `working_hours` object only |
| `shift_information` | `ess.access` | `hrms` | `shift_info` object only |
| `leave_balance` | `leave.view` | `hrms` | Leave balances |
| `manager_attendance` | `manager.dashboard` | `hrms` | Team summary |
| `notifications` | none | `notifications` | Recent notifications |

#### Example: `employee_attendance`

```json
{
  "available": true,
  "state": "checked_in",
  "state_label": "Checked In",
  "working_hours": { },
  "shift_info": { },
  "indicator": { },
  "actions": { }
}
```

Same structure as `GET /attendance/dashboard` data (minus `date`, `on_leave_today`).

#### Example: `leave_balance`

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

Returns empty if no linked employee or `leave_balances` table missing.

#### Example: `notifications`

```json
{
  "notifications": [
    {
      "id": "uuid",
      "title": "Leave Approved",
      "message": "Your leave request was approved.",
      "action_url": "/hrms/ess/leave",
      "read_at": null,
      "created_at": "2026-07-21T10:00:00+00:00"
    }
  ],
  "unread_count": 3
}
```

Default limit: 5 (configurable via widget configuration).

#### Example: `manager_attendance`

```json
{
  "team_summary": {
    "date": "2026-07-21",
    "team_count": 10,
    "present": 8,
    "absent": 1,
    "leave": 1,
    "late": 2,
    "working": 5,
    "checked_out": 3,
    "not_checked_in_count": 1,
    "late_employees": [],
    "attendance_url": "..."
  }
}
```

---

### POST `/api/v1/dashboard/widgets/{widget}/refresh`

Force refresh widget cache.

---

### GET `/api/v1/dashboard/quick-actions`

Quick action links for dashboard.

#### Response

```json
{
  "quick_actions": [
    {
      "module": "hrms",
      "name": "Mark Attendance",
      "icon": "heroicon-o-finger-print",
      "route": "ess.attendance.index",
      "permission_slug": "ess.access",
      "subscription_module": "hrms",
      "sort_order": 10
    },
    {
      "module": "hrms",
      "name": "Apply Leave",
      "icon": "heroicon-o-sun",
      "route": "ess.leave.index",
      "permission_slug": "ess.access",
      "subscription_module": "hrms",
      "sort_order": 20
    }
  ]
}
```

> **Mobile note:** `route` values are Laravel route names pointing to web URLs. Map to native screens when APIs exist.

---

### GET `/api/v1/dashboard/recent-activities`

Recent audit/activity feed.

**Permission:** `audit.view` (widget `recent_activities`)

---

### Dashboard Preferences

| Method | Path | Permission |
|--------|------|------------|
| GET | `/dashboard/preferences` | `dashboard.view` |
| POST | `/dashboard/preferences` | `dashboard.customize` |
| DELETE | `/dashboard/preferences` | `dashboard.customize` |

---

## Dedicated Attendance Dashboard

Prefer `GET /api/v1/attendance/dashboard` for attendance-focused screens — same data as `employee_attendance` widget plus `date` and `on_leave_today`.

See [attendance.md](./attendance.md).

---

## Manager Dashboard

| Endpoint | Purpose |
|----------|---------|
| `GET /attendance/team-summary` | Team KPIs |
| `GET /dashboard/widgets/manager_attendance/data` | Same KPIs via widget |

Web manager dashboard (`ManagerDashboardController`) includes additional data not in API:

- `on_leave_today` employee list
- Pending leave approvals
- Pending attendance corrections

---

## Mobile Screen Mapping

### Employee Home

```
GET /api/v1/attendance/dashboard
GET /api/v1/dashboard/widgets/leave_balance/data
GET /api/v1/dashboard/widgets/notifications/data
GET /api/v1/dashboard/quick-actions
```

### Manager Home

```
GET /api/v1/attendance/team-summary
GET /api/v1/dashboard/widgets/manager_attendance/data
```

### Widget Polling Strategy

| Widget | Suggested refresh |
|--------|-----------------|
| `employee_attendance` | On app resume + after check-in/out |
| `working_hours` | Every 60s while checked in |
| `leave_balance` | On screen open |
| `notifications` | On app resume |

---

## Permissions Summary

| Permission | Slug | Description |
|------------|------|-------------|
| View dashboard | `dashboard.view` | Access dashboard APIs |
| Customize dashboard | `dashboard.customize` | Preferences mutations |
| ESS access | `ess.access` | Attendance widgets |
| Leave view | `leave.view` | Leave balance widget |
| Manager dashboard | `manager.dashboard` | Team attendance widget |

Default `employee` role includes: `dashboard.view`, `ess.access`, `leave.view`, `employee.directory`.
