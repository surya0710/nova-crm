# Recruitment Reporting

## Purpose
Provide executive and operational recruitment reports, saved report configurations, filtered exports, and audit trails.

## Report Types
- Recruitment Summary
- Recruiter Performance
- Hiring Manager Performance
- Department Hiring
- Open Positions
- Pipeline Report
- Offer Report
- Source Report
- Vacancy Aging

## Saved Reports
Table: `recruitment_saved_reports`

Fields: `organization_id`, `user_id`, `report_name`, `report_type`, `filters_json`, `is_shared`, timestamps.

Saved reports belong to the creating user unless shared with the organization (`is_shared`).

## Exports
Supported formats:
- CSV
- Excel (XLSX)
- PDF — future placeholder (rejected with validation message)

Exports respect applied period/type filters and write `recruitment_report_exported` audit events.

## Audit Events
- `recruitment_report_created`
- `recruitment_report_deleted`
- `recruitment_report_shared`
- `recruitment_report_exported`

## Permissions
- View reports: `recruitment.reports.view`
- Export: `recruitment.reports.export`
- Manage/save/share: `recruitment.reports.manage`

## Notifications
Scheduled weekly/monthly email delivery is a future placeholder only. Architecture leaves room for scheduled report jobs without implementing them in this phase.

## Routes
| Route | Permission |
|-------|------------|
| `hrms.recruitment.reports.index` | `recruitment.reports.view` |
| `hrms.recruitment.saved-reports.*` | `recruitment.reports.view` / manage |
| `hrms.recruitment.exports.*` | `recruitment.reports.export` |
