# Employee Dashboard — Attendance (Release 1.1.S.2)

## Overview

Release **1.1.S.2** surfaces attendance on the ESS dashboard and manager dashboard using reusable widgets and `AttendanceDashboardService`.

## Architecture

```
ESS / Manager Dashboard
        ↓
AttendanceDashboardService
        ↓
AttendanceService (clock in/out, metrics, leave/holiday/weekend rules)
        ↓
AttendanceRecord / HrmsShift / Holiday models
```

## Employee dashboard

Route: `GET /hrms/ess` (`ess.dashboard`)

Features:
- Check In / Check Out from dashboard
- Current status (Not Checked In, Checked In, Checked Out, On Leave, Holiday, Weekend)
- Working hours with live timer while checked in
- Shift information (name, times, branch)
- Attendance indicators (On Time, Late, Early, Missing Checkout)
- Recent attendance history
- Upcoming holidays

## Manager dashboard

Route: `GET /hrms/manager/dashboard` (`hrms.manager.dashboard`)

KPIs:
- Present, Late, Leave, Absent, Working, Checked Out
- Late employees list
- On leave today

## Dashboard widgets

Registered in `config/dashboard.php`:

| Key | Provider |
|-----|----------|
| `employee_attendance` | `EmployeeAttendanceWidgetProvider` |
| `working_hours` | `WorkingHoursWidgetProvider` |
| `shift_information` | `ShiftWidgetProvider` |
| `manager_attendance` | `ManagerAttendanceWidgetProvider` |
| `mark_attendance` | Enhanced `MarkAttendanceWidgetProvider` |

## API

| Method | Endpoint | Permission |
|--------|----------|------------|
| GET | `/api/v1/attendance/dashboard` | `ess.access` |
| POST | `/api/v1/attendance/check-in` | `ess.access` |
| POST | `/api/v1/attendance/check-out` | `ess.access` |
| GET | `/api/v1/attendance/team-summary` | `manager.dashboard` |

Organization context: `X-Organization-Id` header required.

## Validation rules

Clock in/out blocked when:
- Employee on approved leave
- Organization/branch holiday
- Weekend (per `config/hrms.php` weekend_days)
- Duplicate check-in or check-out
- Check-out before check-in

Business logic lives in `AttendanceService`; dashboard aggregation in `AttendanceDashboardService`.

## Related docs

- [Attendance API](../api/attendance.md)
- [HRMS user guide — attendance](./user-guide/attendance.md)
