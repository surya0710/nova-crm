# Project Reporting

## Purpose
Generate downloadable project reports (PDF, Excel, CSV) stored on disk with audit trail and workflow integration.

## Report Types
From `config/projects.php` → `report_types`:

| Type | Scope | Content |
| --- | --- | --- |
| `summary` | Project | Project fields + statistics |
| `task_progress` | Project/org | Task rows (optional status filter) |
| `resource_utilization` | Project/org | Allocation rows |
| `milestone_status` | Project | Milestone planned vs actual progress |
| `time_tracking` | Project/org | Task time log rows |
| `timeline` | Project | Timeline + Gantt payload |
| `executive` | Project | Executive summary + critical milestones |

## Formats
`report_formats`: `pdf` (DomPDF), `excel` (PhpSpreadsheet `.xlsx`), `csv`.

## Service
`ProjectReportingService::generate($project, $organization, $reportType, $format, $filters, $actor)`:

1. Validate type and format
2. Build typed payload via `buildPayload()`
3. Export to `storage/app/project-reports/{organization_id}/{type}-{projectId}-{timestamp}.{ext}`
4. Create `ProjectReport` with `storage_path`, `filters`, `generated_at`
5. Dispatch `ReportGenerated` (project-scoped) and notify generator (+ manager)

Project-required types throw validation error when `$project` is null.

## Data Model
| Field | Description |
| --- | --- |
| `report_type` | Key from config |
| `storage_path` | Relative path under `storage/app` |
| `generated_by` | User who requested export |
| `filters` | JSON filters (e.g. task status) |
| `generated_at` | Timestamp |

## Permissions
| Slug | Capability |
| --- | --- |
| `projects.reports.view` | List reports, search |
| `projects.reports.generate` | Create/download reports |

## UI
- `GET/POST projects/{project}/reports` — list + generate form
- `GET projects/{project}/reports/{report}/download` — file download

Blade: `resources/views/projects/reports/index.blade.php`.

## Related Documentation
See [progress-api](progress-api.md) and [progress-user-guide](progress-user-guide.md).
