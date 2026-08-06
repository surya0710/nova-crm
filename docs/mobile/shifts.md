# Shift APIs

Shift information available to mobile clients.

---

## API Availability

| Endpoint | Status |
|----------|--------|
| `GET /api/v1/shifts/current` | **Not implemented** |
| `GET /api/v1/shifts/schedule` | **Not implemented** |
| `GET /api/v1/shifts/weekly` | **Not implemented** |
| `GET /api/v1/shifts/upcoming` | **Not implemented** |
| `GET /api/v1/lookups/shifts` | **Available** (admin search only) |
| Embedded in attendance dashboard | **Available** |

---

## Current Shift — Attendance Dashboard

`GET /api/v1/attendance/dashboard` → `data.shift_info`

Requires `ess.access`.

```json
{
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
  }
}
```

When no shift assigned:

```json
{
  "shift_info": {
    "available": false,
    "phase": null
  }
}
```

### Phase Values

| Phase | Condition |
|-------|-----------|
| `upcoming` | Before shift start |
| `current` | Within shift window, not checked out |
| `completed` | After shift end or checked out |

---

## Dashboard Widget

`GET /api/v1/dashboard/widgets/shift_information/data`

Returns `shift_info` object only (same as attendance dashboard).

---

## Shift Lookup (Admin Search)

`GET /api/v1/lookups/shifts?q=`

| Requirement | Value |
|-------------|-------|
| Permission | `hrms.view` |
| Module | `hrms` |

Returns shift definitions for pickers — **not** employee assignment schedule.

```json
{
  "data": [
    {
      "id": 3,
      "label": "General Shift",
      "subtitle": "09:00 - 18:00",
      "badge": "GEN",
      "metadata": {}
    }
  ]
}
```

---

## Shift Resolution Logic

From `AttendanceService::resolveShiftForEmployee()`:

Uses `EmployeeShiftAssignment` where:

- `effective_from <= date`
- `effective_to` is null OR `>= date`
- Latest matching assignment wins

Overnight shifts: `is_overnight` flag extends end time to next day.

---

## Shift Model Fields (Reference)

From `HrmsShift` model (used in attendance):

| Field | Description |
|-------|-------------|
| `name` | Display name |
| `code` | Short code |
| `start_time` | e.g. `"09:00"` |
| `end_time` | e.g. `"18:00"` |
| `break_minutes` | Deducted from working time |
| `working_hours` | Expected hours (decimal) |
| `grace_period_minutes` | Late tolerance |
| `is_overnight` | Crosses midnight |

---

## Web Admin (Not API)

| Feature | Route | Permission |
|---------|-------|------------|
| Shift CRUD | `/hrms/shifts` | `attendance.manage` |
| Assignments | `/hrms/shift-assignments` | `attendance.manage` |

---

## Weekly Schedule

**Not available via API.**

Web ESS does not expose weekly schedule either. Would require new endpoint querying `EmployeeShiftAssignment` for date range.

---

## Recommended Future API

```
GET /api/v1/ess/shifts/current
GET /api/v1/ess/shifts/schedule?from=2026-07-21&to=2026-07-27
```

Response example:

```json
{
  "data": {
    "today": { },
    "schedule": [
      {
        "date": "2026-07-21",
        "shift": {
          "name": "General Shift",
          "start_time": "09:00",
          "end_time": "18:00"
        },
        "is_weekend": false,
        "is_holiday": false
      }
    ]
  }
}
```

---

## Mobile Screen Mapping

```
Shift Screen
  ↓
GET /attendance/dashboard  → shift_info
  OR
GET /dashboard/widgets/shift_information/data
```

Weekly calendar view: **blocked until API exists**.
