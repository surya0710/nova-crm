# Attendance Reports (Release 1.2)

HR and managers can generate monthly attendance intelligence from the same day-status logic used by the Attendance Calendar. Reports are compiled in `AttendanceReportService` (reusing `AttendanceCalendarService`) and exported without duplicating business rules.

## Report types

| Key | Label | Description |
|-----|-------|-------------|
| `monthly_attendance` | Monthly Attendance | Per-employee month summary: present, late, half day, absent, leave, holiday, weekend, WFH |
| `late_report` | Late Report | Individual late clock-ins in the selected month (date, check-in, late minutes, shift) |
| `absent_report` | Absent Report | Calendar days marked absent for each employee in the selected month |
| `leave_summary` | Leave Summary | Approved and pending leave applications overlapping the selected month |

Configured in `config/hrms.php` under `attendance_reports.types`.

## Filters

| Filter | Behavior |
|--------|----------|
| Report type | Selects one of the four report types above |
| Month / Year | Defaults to the current month; year bounds follow attendance calendar config |
| Department | Optional; limits employees to the selected department |
| Employee | Optional; scopes the report to a single employee |

Empty filters mean **all clockable employees** in the organization.

## Exports

| Format | Key | Notes |
|--------|-----|-------|
| CSV | `csv` | UTF-8 with BOM for Excel compatibility |
| Excel | `xlsx` | PhpSpreadsheet workbook |
| PDF | `pdf` | Landscape DomPDF table |

Configured in `config/hrms.php` under `attendance_reports.export_formats`.

Exports are audited as `attendance_report_exported`.

## Routes

| Route | Name | Description |
|-------|------|-------------|
| `GET /hrms/attendance/reports` | `hrms.attendance.reports.index` | Report UI (generate + table) |
| `GET /hrms/attendance/reports/export` | `hrms.attendance.reports.export` | Download CSV / Excel / PDF |

Query parameters: `report_type`, `year`, `month`, `department_id`, `employee_id`, and for export also `format`.

## Services

| Service | Responsibility |
|---------|----------------|
| `AttendanceReportService` | Compiles report rows, columns, and totals |
| `AttendanceReportExportService` | Streams/downloads CSV, XLSX, PDF |
| `AttendanceCalendarService` | Shared month grid / absent / summary calculations (no duplicate logic) |

## Request validation

`AttendanceReportFilterRequest`

- Requires `attendance.view`
- Validates report type, year/month, department, employee, and export format against config catalogs

## Permissions

- `attendance.view` — view reports and export

## UI entry points

- HR sidebar: **Attendance → Reports**
- Attendance Calendar actions: **Reports**
- Daily Summary page remains separate (`hrms.attendance.summary`) for single-day org KPIs

## Payload shape

```json
{
  "report_type": "monthly_attendance",
  "report_label": "Monthly Attendance",
  "filters": {
    "year": 2026,
    "month": 7,
    "month_label": "July 2026",
    "department_id": null,
    "employee_id": null
  },
  "generated_at": "2026-07-28T16:00:00+00:00",
  "columns": [{ "key": "employee_name", "label": "Employee" }],
  "rows": [],
  "totals": { "employees": 0, "present": 0, "late": 0, "absent": 0, "leave": 0 }
}
```

## Tests

`tests/Feature/AttendanceReportTest.php`

- Reports page renders
- Monthly / late / leave rows compile correctly
- Department filter scopes employees
- CSV export downloads
