# Attendance Calendar (Release 1.1.S.4)

The Attendance module opens on a **monthly calendar** instead of a table. All day status, leave, holiday, and weekend calculations reuse existing services — no duplicate business logic.

## Navigation

Month and year changes load asynchronously via `GET /api/v1/attendance/calendar` without a full page reload.

Controls:

- Previous / Next month
- Month and year dropdowns
- **Today** returns to the current month
- Year range defaults to current year ±5 (`HRMS_CALENDAR_YEAR_RANGE_BEFORE` / `HRMS_CALENDAR_YEAR_RANGE_AFTER`)

Filters (employee, team view) are preserved in the URL and across async reloads.

## Routes

| Route | Audience | Description |
|-------|----------|-------------|
| `GET /hrms/attendance` | HR / managers | Calendar (default attendance view) |
| `GET /hrms/attendance/records` | HR | Legacy table view |
| `GET /hrms/ess/attendance` | Employees | ESS calendar with check-in/out |
| `GET /api/v1/attendance/calendar` | API | Month payload (`year`, `month`, optional `employee_id`, `team=1`) |

## Services

- **`AttendanceCalendarService`** — Builds month grid, summary cards, leave balances, timeline, and team overview.
- Delegates to **`AttendanceService`**, **`AttendanceDashboardService`**, and **`LeaveService`**.

## Visual legend

Present, absent, approved/pending leave, holidays, weekends, half days, remote work (mobile/API source), late and missing-checkout indicators.

## Permissions

- `attendance.view` — HR calendar, employee filter, records table
- `ess.access` — Employee calendar and API
- `manager.dashboard` — Team calendar toggle

## Tests

`tests/Feature/AttendanceCalendarTest.php`
