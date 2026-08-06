# Holiday APIs

Holiday calendar endpoints for mobile.

---

## API Availability

| Endpoint | Status |
|----------|--------|
| `GET /api/v1/holidays` | **Not implemented** |
| `GET /api/v1/holidays/upcoming` | **Not implemented** |
| `GET /api/v1/holidays/{id}` | **Not implemented** |
| Embedded in `AttendanceDashboardService` | Computed but **not exposed** via API |

---

## Computed but Not Exposed

`AttendanceDashboardService::upcomingHolidays()` fetches next 5 holidays for employee's org/branch but `AttendanceDashboardApiController::serializeEmployeeSummary()` **strips** this data from API response.

Internal query:

```php
Holiday::query()
    ->where('organization_id', $employee->organization_id)
    ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id))
    ->whereDate('holiday_date', '>=', today())
    ->orderBy('holiday_date')
    ->limit(5)
```

---

## Holiday Impact on Attendance

When today is a holiday:

- Attendance state: `holiday`
- Check-in/out blocked with message: "Cannot record attendance on a holiday."
- `actions.blocked_reason`: "Today is a holiday."

Detectable via `GET /attendance/dashboard` → `state: "holiday"`.

---

## Web Admin (Not API)

**Route:** `/hrms/holidays`  
**Controller:** `HrmsHolidayController`  
**Permission:** `attendance.manage` (typical HR admin)

CRUD for organization and branch-specific holidays.

---

## Holiday Model Fields (Reference)

| Field | Description |
|-------|-------------|
| `name` | Holiday name |
| `holiday_date` | Date |
| `branch_id` | null = org-wide, set = branch-specific |
| `organization_id` | Tenant scope |
| `description` | Optional description |

---

## Export Workaround (Admin Only)

`POST /api/v1/exports/sessions` with entity `holiday` — async bulk export, not suitable for mobile calendar UI.

---

## Recommended Future API

```
GET /api/v1/ess/holidays/upcoming?limit=10
GET /api/v1/ess/holidays?year=2026&month=7
GET /api/v1/ess/holidays/{id}
```

### Example Response

```json
{
  "data": [
    {
      "id": 1,
      "name": "Independence Day",
      "holiday_date": "2026-08-15",
      "branch": null,
      "scope": "organization"
    },
    {
      "id": 2,
      "name": "Branch Anniversary",
      "holiday_date": "2026-09-01",
      "branch": { "id": 3, "name": "Mumbai" },
      "scope": "branch"
    }
  ]
}
```

Permission: `ess.access` or `employee.directory`

---

## Mobile Screen Mapping

```
Holidays Screen
  ↓
[NOT AVAILABLE — request backend to expose upcoming_holidays in dashboard API]
  ↓
Workaround: infer from attendance state === 'holiday' for today only
```

Quick fix for backend: add `upcoming_holidays` to `serializeEmployeeSummary()` in `AttendanceDashboardApiController`.

---

## Organization Calendar (Web)

**Route:** `/hrms/organization-calendar`  
**Permission:** `organization.calendar`

Combined view of holidays, leaves, events — web only.
